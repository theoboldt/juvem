<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewsletterSubscriptionImportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:newsletter:import')
            ->setDescription('Import newsletter subscriptions from csv file')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to the csv file to import');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        if (!is_readable($path) || is_dir($path)) {
            $output->writeln('<error>Transmitted csv file is not readable or directory</error>');
            return 1;
        }

        $progress = new ProgressBar($output);
        $progress->start();
        try {
            $result = $this->readImportFile(
                $path,
                function () use ($progress) {
                    $progress->advance();
                }
            );
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>Failed to read csv: ' . $e->getMessage() . '</error>');
            return 2;
        } finally {
            $progress->finish();
        }
        $output->writeln(sprintf("\n       Read <info>%d</info> entries from file", count($result)));

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $table = new Table($output);
            $table->setHeaders(['Name', 'Base date', 'Length', 'E-Mail', 'Errors']);
            foreach ($result as $row) {
                $row['errors'] = implode(', ', $row['errors']);
                $table->addRow(array_values($row));
            }
            $table->render();
        }
    }

    /**
     * Read import csv file
     *
     * @param string        $path         Path to file to import
     * @param null|callable $stepCallback Callback called each time a row was read
     * @return array                      Result subscriptions
     */
    protected function readImportFile($path, $stepCallback = null)
    {
        $result = [];

        $row = 1;
        if (($handle = fopen($path, 'r')) !== false) {
            while (($rowCellList = fgetcsv($handle, 1000, ";")) !== false) {
                $cellCount = count($rowCellList);
                if ($cellCount != 5) {
                    throw new \InvalidArgumentException('Row ' . $row . ' contains unexpected amount of columns');
                }

                if ($rowCellList[0] == 'Name' || max($rowCellList) == '') {
                    //this is header, so skip
                    continue;
                }

                $errors = [];

                $rowBirthday = \DateTime::createFromFormat('y-m-d', $rowCellList[1]);
                if (!$rowBirthday) {
                    $errors[] = 'Invalid Birthday';
                }
                if (!is_numeric($rowCellList[3])) {
                    $errors[] = 'Invalid stretch factor';
                }
                if (filter_var(trim($rowCellList[4]), FILTER_VALIDATE_EMAIL) === false) {
                    $errors[] = 'Invalid email';
                }

                $result[] = [
                    'name'      => $rowCellList[0],
                    'birthday'  => $rowCellList[1],
                    'age_space' => intval($rowCellList[3], 10),
                    'email'     => trim($rowCellList[4]),
                    'errors'    => $errors
                ];

                if (is_callable($stepCallback)) {
                    $stepCallback();
                }

                $row++;
            }
            fclose($handle);
        }

        return $result;
    }
}
