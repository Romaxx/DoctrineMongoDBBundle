<?php


namespace Doctrine\Bundle\MongoDBBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Generate document classes from mapping information
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class GenerateDocumentsDoctrineODMCommand extends DoctrineODMCommand
{
    protected function configure()
    {
        $this
            ->setName('doctrine:mongodb:generate:documents')
            ->setDescription('Generate document classes and method stubs from your mapping information.')
            ->addArgument('bundle', InputArgument::OPTIONAL, 'The bundle to initialize the document or documents in.')
            ->addOption('document', null, InputOption::VALUE_OPTIONAL, 'The document class to initialize (shortname without namespace).')
            ->addOption('no-backup', null, InputOption::VALUE_NONE, 'Do not backup existing entities files.')
            ->setHelp(<<<EOT
The <info>doctrine:mongodb:generate:documents</info> command generates document classes and method stubs from your mapping information:

For SF = 4, you can generate all the documents with the command:

  <info>php app/console doctrine:mongodb:generate:documents</info>

For SF < 4 you have to specify individual bundle:

  <info>php app/console doctrine:mongodb:generate:documents MyCustomBundle</info>

Alternatively, you can limit generation to a single document within a bundle:

  <info>php app/console doctrine:mongodb:generate:documents "MyCustomBundle" --document="User"</info>

You have to specify the shortname (without namespace) of the document you want to filter for.

By default, the unmodified version of each document is backed up and saved
(e.g. ~Product.php). To prevent this task from creating the backup file,
pass the <comment>--no-backup</comment> option:

  <info>php app/console doctrine:mongodb:generate:documents MyCustomBundle --no-backup</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundleName = $input->getArgument('bundle');
        $filterDocument = $input->getOption('document');


        if (!is_null($bundleName)) {
            $foundBundle = $this->findBundle($bundleName);
        }else{
            $foundBundle = null;
        }
            if ($metadatas = $this->getBundleMetadatas($foundBundle)) {

                if (is_null($foundBundle)){
                    $output->writeln(sprintf('Generating documents for all the project'));
                }else{
                    $output->writeln(sprintf('Generating documents for "<info>%s</info>"', $foundBundle->getName()));
                }

                $documentGenerator = $this->getDocumentGenerator();
                $documentGenerator->setBackupExisting(!$input->getOption('no-backup'));

                foreach ($metadatas as $metadata) {
                    if ($filterDocument && $metadata->getReflectionClass()->getShortName() != $filterDocument) {
                        continue;
                    }

                    if ($foundBundle instanceof Bundle) {
                        if (strpos($metadata->name, $foundBundle->getNamespace()) === false) {
                            throw new \RuntimeException(
                                "Document " . $metadata->name . " and bundle don't have a common namespace, " .
                                "generation failed because the target directory cannot be detected.");
                        }
                    }

                    $output->writeln(sprintf('  > generating <comment>%s</comment>', $metadata->name));
                    $documentGenerator->generate([$metadata], $this->findBasePathForBundle($foundBundle));
                }
            } else {
                throw new \RuntimeException(
                    "Bundle " . $bundleName . " does not contain any mapped documents." .
                    "Did you maybe forget to define a mapping configuration?");
            }


    }
}
