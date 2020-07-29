<?php
/**
 * file:cleanup command for Skeleton Console
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class File_Cleanup extends \Skeleton\Console\Command {

	/**
	 * Configure the cleamup command
	 *
	 * @access protected
	 */
	protected function configure() {
		$this->setName('file:cleanup');
		$this->setDescription('Clean up the file store');
		$this->addArgument('type', InputArgument::REQUIRED, 'What to clean up, accepts "leaves"');
	}

	/**
	 * Execute the Command
	 *
	 * @access protected
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$type = $input->getArgument('type');

		if ($type === 'leaves') {
			if (\Skeleton\File\Config::$store_dir === null && \Skeleton\File\Config::$file_dir === null) {
				$output->writeln('<error>skeleton-file is not properly configured, store_dir and file_dir missing</error>');
				return 1;
			}

			if (\Skeleton\File\Config::$file_dir !== null) {
				$store_path = \Skeleton\File\Config::$file_dir . '/';
			} else {
				$store_path = \Skeleton\File\Config::$store_dir . '/file/';
			}

			$store_path = realpath($store_path);

			if ($store_path === false) {
				$output->writeln('<error>skeleton-file is not properly configured, resolved store path is incorrect</error>');
				return 1;
			}

			// Loop over all nodes and remove empty directories recursively
			$directory_iterator = new \RecursiveDirectoryIterator(realpath($store_path), \FilesystemIterator::SKIP_DOTS);
			$iterator = new \RecursiveIteratorIterator($directory_iterator, \RecursiveIteratorIterator::CHILD_FIRST);
			$iterator->setMaxDepth(2);

			foreach ($iterator as $node) {
				if ($node->isDir() && !(new \FilesystemIterator($node->getPathname()))->valid()) {
					rmdir((string)$node);
				}
			}
		} else {
			$output->writeln('<error>Unsupported cleanup type</error>');
			return 1;
		}

		$output->writeln('All done');
		return 0;
	}
}
