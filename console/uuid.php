<?php
/**
 * file:uuid command for Skeleton Console
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
use \Skeleton\Database\Database;

class File_Uuid extends \Skeleton\Console\Command {

	/**
	 * Configure the Create command
	 *
	 * @access protected
	 */
	protected function configure() {
		$this->setName('file:uuid');
		$this->setDescription('Manage File Uuid');
		$this->addArgument('action', InputArgument::REQUIRED, 'generate');
	}

	/**
	 * Execute the Command
	 *
	 * @access protected
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$action = $input->getArgument('action');
		if (!is_callable([ $this, $action ])) {
			$output->writeln('<error>Please specify a valid action: generate</error>');
			return 1;
		}
		return $this->$action($input, $output);
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
