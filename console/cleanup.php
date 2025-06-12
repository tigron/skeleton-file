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
		$this->addArgument('mode', InputArgument::OPTIONAL, 'How to clean up, accepts "dryrun" or nothing for real execution');
	}

	/**
	 * Execute the Command
	 *
	 * @access protected
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$type = $input->getArgument('type');
		$dryrun = $input->getArgument('mode');
		if (strtoupper($dryrun) == 'DRYRUN' || strtoupper($dryrun) == 'DRY-RUN') {
			$dryrun = true;
		} else {
			$dryrun = false;
		}

		if (\Skeleton\File\Config::$file_dir !== null) {
			\Skeleton\File\Config::$file_path = \Skeleton\File\Config::$file_dir;
		} elseif (\Skeleton\File\Config::$store_dir !== null) {
			\Skeleton\File\Config::$file_path = \Skeleton\File\Config::$file_dir . '/file';
		} elseif (Config::$file_path === null) {
			$output->writeln('<error>skeleton-file is not properly configured, store_dir and file_dir missing</error>');
			return 1;
		}

		$store_path = realpath(\Skeleton\File\Config::$file_path . '/');

		if ($store_path === false) {
			$output->writeln('<error>skeleton-file is not properly configured, resolved store path is incorrect</error>');
			return 1;
		}

		if ($type === 'leaves') {
			// Loop over all nodes and remove empty directories recursively
			$directory_iterator = new \RecursiveDirectoryIterator(realpath($store_path), \FilesystemIterator::SKIP_DOTS);
			$iterator = new \RecursiveIteratorIterator($directory_iterator, \RecursiveIteratorIterator::CHILD_FIRST);
			$iterator->setMaxDepth(2);

			foreach ($iterator as $node) {
				if ($node->isDir() && !(new \FilesystemIterator($node->getPathname()))->valid()) {
					if ($dryrun) {
						if (count(scandir($node)) == 0) {
							printf("%s\n", (string)$node);
						}
					} else {
						rmdir((string)$node);
					}
				}
			}
		} else if ($type == 'orphans') {
			$db = \Skeleton\Database\Database::Get();
			$offset = 0;
			do {
				$rows = $db->get_all("SELECT id, path FROM file LIMIT 100 OFFSET " . $offset);
				foreach ($rows as $row) {
					if (file_exists(realpath($store_path . '/' . $row['path'])) == false) {
						if ($dryrun) {
							printf("File #%d (%s) not found\n", $row['id'], $row['path']);
						} else {
							printf("File #%d (%s) not found; deleting\n", $row['id'], $row['path']);
							try {
								$db->query("DELETE FROM file WHERE id = ?", [ $row['id'] ]);
								$db->query("DELETE FROM picture WHERE file_id = ?", [ $row['id'] ]);
							} catch (\Exception $e) {
								printf("  %s\n", $e->getMessage());
							}
						}
					}
				}
				$offset += 100;
			} while(count($rows) == 100);
		} else {
			$output->writeln('<error>Unsupported cleanup type</error>');
			return 1;
		}

		$output->writeln('All done');
		return 0;
	}
}
