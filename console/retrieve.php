<?php
/**
 * file:retrieve command for Skeleton Console
 *
 * @author Lionel Laffineur <lionel@tigron.be>
 */

namespace Skeleton\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class File_Retrieve extends \Skeleton\Console\Command {

	/**
	 * Configure the Create command
	 *
	 * @access protected
	 */
	protected function configure() {
		$this->setName('file:retrieve');
		$this->setDescription('Retrieve a file from file store');
		$this->addArgument('id', InputArgument::REQUIRED, '');
		$this->addArgument('dir', InputArgument::OPTIONAL, '');
	}

	/**
	 * Execute the Command
	 *
	 * @access protected
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$id = $input->getArgument('id');
		$dir = $input->getArgument('dir');

		try {
			$file = \Skeleton\File\File::get_by_id($id);
			if ($dir != '') {
				if (file_exists($dir) == false) {
					$output->writeln('<error>Directory ' . $dir . ' not found</error>');
					return 1;
				}
				if (is_dir($dir) == false) {
					$output->writeln('<error>Directory ' . $dir . ' appears to be a file</error>');
					return 1;
				}
				file_put_contents($dir . '/' . $file->name, $file->get_contents());
			} else {
				file_put_contents($file->name, $file->get_contents());
			}
		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . ': file retrieve failed</error>');
			return 1;
		}

		return 0;
	}
}
