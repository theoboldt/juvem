<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Command;


use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            $password = file_get_contents($password);
        }
        
        $archiveFlags = \ZipArchive::CREATE;
        $targetExists = file_exists($this->path);
        if ($input->isInteractive() && $targetExists) {
            $helper       = $this->getHelper('question');
            $question     = new ConfirmationQuestion('Target file already exists, overwrite?', false);
            $archiveFlags = \ZipArchive::OVERWRITE;
            
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Nothing exported');
                return 0;
            }
        }
        $this->cleanup();
        
        try {
            $this->disableService();
            $output->write('Creating database image... ');
            if (!$this->addDatabaseDump($input, $output)) {
                $output->writeln('aborted.');
                $this->enableService();
                return 1;
            }
            $output->writeln('done.');
            
            $output->write('Collecting data files to export... ');
            $this->addDataFiles();
            $output->writeln('done.');
            
            if ($password) {
                $output->writeln('Creating archive with password...');
            } else {
                $output->writeln('Creating archive without password...');
            }
            
            $archive = new \ZipArchive();
            if (!$archive->open($this->path, $archiveFlags)) {
                $output->writeln(
                    '<error>Failed to open "' . $this->path . '", ' . $archive->getStatusString() . '</error>'
                );
                $archive->close();
                $this->cleanup();
                return 4;
            }
            if ($password) {
                $archive->setPassword($password);
            }
            
            $output->writeln('Adding files to export...');
            $progress = new ProgressBar($output, count($this->files));
            foreach ($this->files as $path => $subPathName) {
                $archive->addFile($path, $subPathName);
                if ($password) {
                    if (!$archive->setEncryptionName($subPathName, \ZipArchive::EM_AES_256, $password)) {
                        $output->writeln(
                            '<error>Failed to encrypt "' . $subPathName . '", ' . $archive->getStatusString() .
                            '</error>'
                        );
                        $archive->close();
                        $this->cleanup();
                        return 3;
                    }
                }
                $progress->advance();
            }
            $progress->finish();
            $output->writeln(' done.');
            
            
            $output->write('Closing... ');
            $archive->close();
            $output->writeln('done.');
            
            $this->cleanup();
        } catch (\Exception $e) {
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
        $container         = $this->getContainer();
        $this->dbImagePath = $container->getParameter('app.tmp.root.path') . '/' . uniqid('db_image');
        
        if (`which mysqldump`) {
            $configurationPath = $container->getParameter('app.database.configuration.path');
            $this->createMysqlConfigurationFile();
            shell_exec(
                sprintf(
                    'mysqldump --defaults-file=%s --single-transaction --add-drop-table --host=%s --port=%d %s > %s',
                    escapeshellarg($configurationPath),
                    escapeshellarg($container->getParameter('database_host')),
                    escapeshellarg($container->getParameter('database_port')),
                    escapeshellarg($container->getParameter('database_name')),
                    escapeshellarg($this->dbImagePath)
                )
            );
            unlink($configurationPath);
            $this->files[$this->dbImagePath] = '/database.sql';
        } else {
            if ($input->isInteractive()) {
                $helper   = $this->getHelper('question');
                $question = new ConfirmationQuestion('Can not add database image, continue?', false);
                
                if (!$helper->ask($input, $output, $question)) {
                    $output->writeln('Nothing exported');
                    return false;
                }
            } else {
                $output->writeln('<error>Can not add database image, mysqldump command missing</error>');
            }
        }
        return true;
    }
    
    /**
     * Add all files of data directory to archive list
     *
     * @return bool Returns true if should continue
     */
    private function addDataFiles(): bool
    {
        $dataPath    = $this->getContainer()->getParameter('app.data.root.path');
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
