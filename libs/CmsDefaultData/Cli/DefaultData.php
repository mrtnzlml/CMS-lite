<?php

namespace CmsDefaultDataExtension\Cli;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Kdyby\Doctrine\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DefaultData extends Command
{

	/** @var EntityManager @inject */
	public $em;

	protected function configure()
	{
		$this
			->setName('cms:demo-data:load')
			->setDescription('Load data fixtures to your database.')
			->addOption('append', NULL, InputOption::VALUE_NONE, 'Append the data fixtures instead of deleting all data from the database first.')
			->addOption('purge-with-truncate', NULL, InputOption::VALUE_NONE, 'Purge data by using a database-level TRUNCATE statement')
			->setHelp(<<<EOT
The <info>cms:demo-data:load</info> command loads data fixtures from your bundles:

  <info>php index.php cms:demo-data:load</info>

If you want to append the fixtures instead of flushing the database first you can use the <info>--append</info> option:

  <info>php index.php cms:demo-data:load --append</info>

By default Doctrine Data Fixtures uses DELETE statements to drop the existing rows from
the database. If you want to use a TRUNCATE statement instead you can use the <info>--purge-with-truncate</info> flag:

  <info>php index.php cms:demo-data:load --purge-with-truncate</info>
EOT
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			if ($input->isInteractive() && !$input->getOption('append')) {
				/** @var QuestionHelper $dialog */
				$dialog = $this->getHelperSet()->get('question');
				$question = new ConfirmationQuestion('<question>Careful, database will be purged. Do you want to continue Y/N ?</question>', FALSE);
				if (!$dialog->ask($input, $output, $question)) {
					return 0; // zero return code means everything is ok
				}
			}

			$loader = new Loader();
			$loader->addFixture(new \UsersFixture()); //FIXME: načítat automaticky (?)
			$fixtures = $loader->getFixtures();

			$purger = new ORMPurger($this->em);
			$purger->setPurgeMode($input->getOption('purge-with-truncate') ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);

			$executor = new ORMExecutor($this->em, $purger);
			$executor->setLogger(function ($message) use ($output) {
				$output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
			});
			$executor->execute($fixtures, $input->getOption('append'));
			return 0; // zero return code means everything is ok
		} catch (\Exception $exc) {
			$output->writeLn("<error>{$exc->getMessage()}</error>");
			return 1; // non-zero return code means error
		}
	}

}
