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

class DataImportCommand extends DataCommandBase
{
    
    /**
     * archive
     *
     * @var \ZipArchive
     */
    private $archive;
    
    /**
     * Contains list of files in data folder before import
     *
     * @var array
     */
    private $dataFilesBefore = [];
    
    /**
     * List of files which are expected to be in data folder after import
     *
     * @var array
     */
    private $dataFilesAfter = [];
    
    /**
     * Files vanish after update
     *
     * @var array
     */
    private $goneFiles = [];
    
    /**
     * New files to appear
     *
     * @var array
     */
    private $newFiles = [];
    
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('app:data:import')
             ->setDescription('Import an exported package containing database and file system contents')
             ->addArgument('path', InputArgument::REQUIRED, 'Location where imported package is located')
             ->addArgument('password', InputArgument::OPTIONAL, 'Password which is used to encrypt archive');
    }
    
    /**
     * Open archive
     *
     * @param OutputInterface $output
     * @param string|null $password Archive password
     */
    private function openArchive(OutputInterface $output, string $password = null)
    {
        $output->write('Opening archive... ');
        $this->archive = new \ZipArchive();
        $this->archive->open($this->path, \ZipArchive::CHECKCONS);
        if ($password) {
            if (!$this->archive->setPassword($password)) {
                $output->writeln('<error>Failed to open using password</error>');
                throw new \RuntimeException();
            }
        }
        $output->writeln('done.');
    }
    
    /**
     * Get archive wrapper
     *
     * @return string
     */
    private function getWrapper()
    {
        return 'phar://' . $this->archive->getPath();
    }
    
    /**
     * Collect expected files
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|int
     */
    private function collectExpectedFiles(InputInterface $input, OutputInterface $output)
    {
        $databaseImageFound = false;
        $output->write('Collecting expected files... ');
        /** @var \PharFileInfo $file */
        
        for ($i = 0; $i < $this->archive->numFiles; $i++) {
            $relativePath = $this->archive->getNameIndex($i);
            if (strpos($relativePath, '/data/') === 0) {
                $this->dataFilesAfter[] = $relativePath;
            }
            if ($relativePath === '/database.sql') {
                $databaseImageFound = true;
            }
            #copy("zip://" . $path . "#" . $filename, "/your/new/destination/" . $fileinfo['basename']);
        }
        $output->writeln('done.');
        sort($this->dataFilesBefore);
        sort($this->dataFilesAfter);
        
        $this->goneFiles = array_diff($this->dataFilesBefore, $this->dataFilesAfter);
        $this->newFiles  = array_diff($this->dataFilesAfter, $this->dataFilesBefore);
        
        if (!$databaseImageFound) {
            $output->writeln('<error>Image does not contain database file - Aborting.</error>');
            return 2;
        }
        
        if ($input->isInteractive()) {
            $helper   = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Will delete <info>' . count($this->goneFiles) . '</info> and add <info>' . count($this->newFiles) .
                '</info> files - Do you want to continue?', false
            );
            
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Nothing imported');
                return 0;
            }
        }
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->path = $input->getArgument('path');
        
        
        if (!file_exists($this->path)) {
            $output->writeln(
                '<error>Import file "' . $this->path . '" is not existing</error>'
            );
        }
        
        if (!`which mysql`) {
            if ($input->isInteractive()) {
                $helper   = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    'No mysql command to import database available, contiune anyway?', false
                );
                
                if (!$helper->ask($input, $output, $question)) {
                    $output->writeln('Nothing imported');
                    return 0;
                }
            } else {
                $output->writeln('<error>No mysql command for import available, aborting</error>');
                return 3;
            }
            
        }
        
        try {
            $this->disableService();
            
            $output->write('Collecting current data files... ');
            $dataPath              = $this->getContainer()->getParameter('app.data.root.path');
            $this->dataFilesBefore = array_values(self::createFileListing($dataPath, '/data/'));
            $output->writeln('done.');
            
            //password handling
            $password = $input->hasArgument('password') ? $input->getArgument('password') : null;
            if (file_exists($password)) {
                $password = file_get_contents($password);
            }
            $this->openArchive($output, $password);
            
            $result = $this->collectExpectedFiles($input, $output);
            if ($result !== true) {
                $this->enableService();
                return $result;
            }
            
            $output->writeln('Deleting gone files... ');
            $progress = new ProgressBar($output, count($this->goneFiles));
            foreach ($this->goneFiles as $path) {
                $filePath = $dataPath . '/' . str_replace('/data/', '', $path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $progress->advance();
            }
            $progress->finish();
            $output->writeln(' done.');
            
            $output->write('Preparing database import... ');
            $databaseImagePath = $this->getContainer()->getParameter('app.tmp.root.path') . '/database.sql';
            if (!$this->archive->extractTo($this->getContainer()->getParameter('app.tmp.root.path'), '/database.sql')) {
                $output->writeln('<error>Failed to extract database file.</error>');
                if ($password) {
                    $output->writeln('The password set might be incorrect');
                }
                throw new \RuntimeException();
            }
            
            $configurationPath = $this->getContainer()->getParameter('app.database.configuration.path');
            $this->createMysqlConfigurationFile();
            $output->writeln('done.');
            
            $output->write('Importing database... ');
            shell_exec(
                sprintf(
                    'mysql --defaults-file=%s --host=%s --port=%d %s < %s',
                    escapeshellarg($configurationPath),
                    escapeshellarg($this->getContainer()->getParameter('database_host')),
                    escapeshellarg($this->getContainer()->getParameter('database_port')),
                    escapeshellarg($this->getContainer()->getParameter('database_name')),
                    escapeshellarg($databaseImagePath)
                )
            );
            $output->writeln('done.');
            unlink($configurationPath);
            unlink($databaseImagePath);
            
            $output->writeln('Extracting files from backup... ');
            $progress = new ProgressBar($output, count($this->dataFilesAfter));
            foreach ($this->dataFilesAfter as $path) {
                $targetPath = dirname($dataPath . '/' . str_replace('/data/', '', $path));
                if (!file_exists($targetPath)) {
                    mkdir($targetPath, 0777, true);
                }
                if (!$this->archive->extractTo($dataPath . '/../', $path)) {
                    $output->writeln('<error>Failed to extract file "'.$path.'"</error>');
                }
                
                $progress->advance();
            }
            $progress->finish();
            $output->writeln(' done.');
            
        } catch (\Exception $e) {
            $this->enableService();
            throw $e;
        }
        $this->enableService();
        
        return 0;
    }
}