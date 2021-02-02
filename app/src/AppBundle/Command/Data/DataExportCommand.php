<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Command\Data;


use AppBundle\Anonymization\DumpAnonymizer;
use Ifsnop\Mysqldump\Mysqldump;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DataExportCommand extends DataCommandBase
{
    
    /**
     * List of files to add to zip
     *
     * @var array|string[]
     */
    private $files = [];
    
    /**
     * Full path to db image
     *
     * @var string|null
     */
    private $dbImagePath = null;
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('app:data:export')
             ->setDescription('Create export package containing database and file system contents')
             ->addArgument('path', InputArgument::REQUIRED, 'Location where export package is saved to')
             ->addOption('exclude-database', 'b', InputOption::VALUE_NONE, 'Exclude database backup from result')
             ->addOption('exclude-data', 'd', InputOption::VALUE_NONE, 'Exclude backup of data files (which would increase backup size significantly if included)')
             ->addOption('anonymize-database', 'a', InputOption::VALUE_NONE, 'Anonymize data of participants')
             ->addArgument('password', InputArgument::OPTIONAL, 'Password which is used to encrypt archive');
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->path = $input->getArgument('path');
        
        //password handling
        $password = $input->hasArgument('password') ? $input->getArgument('password') : null;
        if (file_exists($password)) {
            $password = file_get_contents(trim($password));
        }
        
        $targetExists = file_exists($this->path);
        if ($input->isInteractive() && $targetExists) {
            $helper    = $this->getHelper('question');
            $question  = new ConfirmationQuestion('Target file already exists, overwrite?', false);
            
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Nothing exported');
                return 0;
            }
        }

        $excludeDatabase = $input->getOption('exclude-database') !== false;
        $excludeData     = $input->getOption('exclude-data') !== false;

        $this->cleanup();
        
        try {
            $this->disableService();
            if ($excludeDatabase) {
                $output->writeln('Skipping database image creation.');
            } else {
                $memoryPeak = round(memory_get_peak_usage(true) / 1024 / 1024); 
                $output->writeln('Peak memory is '.$memoryPeak.' MiB');
                $output->write('Creating database image... ');
                $start = microtime(true);
                if (!$this->addDatabaseDump($input, $output)) {
                    $output->writeln('aborted.');
                    $this->enableService();
                    return 1;
                }
                $memoryPeakDb = round(memory_get_peak_usage(true) / 1024 / 1024); 
                $output->writeln(
                    'done in ' . round(microtime(true) - $start) . ' s using peak of ' .
                    $memoryPeakDb . ' MiB.'
                );
            }
            
            if ($excludeData) {
                $output->writeln('Skipping collection of data files.');
            } else {
                $output->write('Collecting data files to export... ');
                $this->addDataFiles();
                $output->writeln('done.');
            }
            
            if ($password) {
                $output->write('Creating archive with password... ');
            } else {
                $output->write('Creating archive without password... ');
            }
    
            $archive        = new \ZipArchive();
            $archiveTmpPath = $this->temporaryFileName('data_export');
            if (!$archive->open($archiveTmpPath, \ZipArchive::CREATE)) {
                $output->writeln('failed.');
                $output->writeln(
                    '<error>Failed to open "' . $archiveTmpPath . '", ' . $archive->getStatusString() . '</error>'
                );
                $archive->close();
                $this->cleanup();
                return 4;
            }
            if ($password) {
                $archive->setPassword($password);
            }
            $output->writeln('done.');
            
            $output->write('Adding files to export...');
            $encryptionError = false;
            $progress = new ProgressBar($output, count($this->files));
            foreach ($this->files as $path => $subPathName) {
                $archive->addFile($path, $subPathName);
                if ($password) {
                    if (!$archive->setEncryptionName($subPathName, \ZipArchive::EM_AES_256, $password)) {
                        if (!$encryptionError) {
                            $output->writeln(' partially failed.');
                        }
                        $output->writeln(
                            '<error>Failed to encrypt "' . $subPathName . '", ' . $archive->getStatusString() .
                            '</error>'
                        );
                        $encryptionError = true;
                        $archive->close();
                        $this->cleanup();
                        return 3;
                    }
                }
                $progress->advance();
            }
            $progress->finish();
            $output->writeln(' done.');
    
            $timeClose = microtime(true);
            $output->write('Closing... ');
            $archive->close();
            $output->writeln(
                'done after <info>' . round(microtime(true) - $timeClose) . ' s</info>.'
            );
    
            $output->write('Moving to target... ');
            $timeMv = microtime(true);
            exec('mv ' . escapeshellarg($archiveTmpPath) . ' ' . escapeshellarg($this->path), $mvOutput, $return);
            if ($return !== 0) {
                $output->writeln(
                    'failed after <info>' . round(microtime(true) - $timeMv) . ' s</info>: <error>' .
                    implode(', ', $mvOutput) . '</error>'
                );
            } else {
                $output->writeln(
                    'done after <info>' . round(microtime(true) - $timeMv) . ' s</info>.'
                );
            }
            $this->cleanup();
        } catch (\Exception $e) {
            $this->cleanup();
            $this->enableService();
            throw $e;
        }
        $this->enableService();
        
        return 0;
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function addDatabaseDump(InputInterface $input, OutputInterface $output): bool
    {
        $anonymizeDatabase = $input->getOption('anonymize-database') !== false;
        $this->dbImagePath = $this->tmpRootPath . '/' . uniqid('db_image');
    
        $settings = [
            'add-drop-table'        => true,
            'single-transaction'    => true,
            'default-character-set' => Mysqldump::UTF8MB4,
        ];
        
        try {
            $dump = new Mysqldump(
                'mysql:host=' . $this->databaseHost . ';port=' . $this->databasePort . ';dbname=' . $this->databaseName,
                $this->databaseUser,
                $this->databasePassword,
                $settings
            );
            if ($anonymizeDatabase) {
                $output->write('anonymized... ');
                $anonymizer = new DumpAnonymizer($dump);
                $anonymizer->__invoke();
            }
            $dump->start($this->dbImagePath);
            $this->files[$this->dbImagePath] = '/database.sql';
        } catch (\Exception $e) {
            $output->writeln(
                sprintf('<error>Error when creating database backup: %s</error>', $e->getMessage())
            );
            return false;
        }
    
        return true;
    }

    /**
     * Create a temporary file in apps tmp dir
     *
     * @param string $prefix Prefix to use
     * @return string        New file name
     */
    private function temporaryFileName(string $prefix): string
    {
        if (!file_exists($this->tmpRootPath)) {
            $umask = umask();
            umask(0);
            if (!mkdir($this->tmpRootPath, 0777, true)) {
                umask($umask);
                throw new \RuntimeException(sprintf('Failed to create %s', $this->tmpDir));
            }
            umask($umask);
        }
        return tempnam($this->tmpRootPath, $prefix);
    }
    
    /**
     * Add all files of data directory to archive list
     *
     * @return bool Returns true if should continue
     */
    private function addDataFiles(): bool
    {
        $dataPath    = $this->dataRootPath;
        $this->files = array_merge($this->files, self::createFileListing($dataPath, '/data/'));
        return true;
    }
    
    /**
     * Cleanup temporary files
     */
    private function cleanup()
    {
        if (file_exists($this->dbImagePath)) {
            unlink($this->dbImagePath);
        }
    }
    
    
}
