<?php
/**
 * file:store command for Skeleton Console
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

class File_Store extends \Skeleton\Console\Command {

	/**
	 * Configure the Create command
	 *
	 * @access protected
	 */
	protected function configure() {
		$this->setName('file:store');
		$this->setDescription('Store a file in file store');
		$this->addArgument('filename', InputArgument::REQUIRED, '');
	}

	/**
	 * Execute the Command
	 *
	 * @access protected
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$filename = $input->getArgument('filename');
		if (file_exists($filename) == false) {
			$output->writeln('<error>Please specify an existing filename</error>');
			return 1;
		}
		if (is_dir($filename)) {
			$output->writeln('<error>Argument specified appears to be a directory, not allowed</error>');
			return 1;
		}
		try {
			$file = \Skeleton\File\File::store(basename($filename), file_get_contents($filename));
		} catch (Exception $e) {
			$output->writeln('<error>' . $e->getMessage . ' - file not stored</error>');
			return 1;
		}
		$output->writeln($file->id);
		return 0;
	}

	/**
	 * Generate
	 *
	 * @access protected
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function generate(InputInterface $input, OutputInterface $output) {
		try {
			ob_start();

			$db = Database::get();
			do {
				$ids = $db->get_column("SELECT id FROM file WHERE uuid IS NULL ORDER BY id LIMIT 100");
				foreach ($ids as $id) {
					$file = \Skeleton\File\File::get_by_id($id);
					$file->save();
				}
			} while (count($ids) > 0);

			$content = ob_get_contents();
			ob_end_clean();
		} catch (\Exception $e) {
			$output->writeln('<error>' . $e->getMessage() . ': Uuid generation failed</error>');
			return 1;
		}
		$output->writeln('<info>' . $content . '</info>');
		return 0;
	}
}
