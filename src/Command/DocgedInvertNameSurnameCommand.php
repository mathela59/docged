<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DocgedInvertNameSurnameCommand extends Command
{
    protected static $defaultName = 'docged:invertNameSurname';
    protected static $defaultDescription = 'Add a short description for your command';
    private $filesystem;
    private $finder;
    public $io;

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description');
        $this->filesystem = new Filesystem();
        /* @var Finder */
        $this->finder = new Finder();

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $dirToScan = $input->getArgument('arg1');

        $finder = new Finder();
        $finder->files()->in($dirToScan)->depth('== 0');
        if(!$finder->hasResults())
        {
            $this->io->error("No files found in "+$_ENV['DIRSRC']);
            return Command::FAILURE;
        }
        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();
            $fileNameWithExtension = $file->getRelativePathname();

            var_dump($absoluteFilePath);
            var_dump($fileNameWithExtension);

//            $match = array();
//            preg_match('/^([A-Z]{1}[a-z]*)([A-Z]{1}[a-z]*)(.*)/', $fileNameWithExtension, $match);
//            var_dump($match);
//            if(count($match)>1) {
//                $this->io->info($match[1] . '####' . $match[2]);
//            }
//            $new  = $match[2].'_'.$match[1].$match[3];
//
//            if (!$this->filesystem->exists($new)) {
//                $this->filesystem->rename($file->getRealPath(), $new);
//                $this->io->info($file->getRealPath());
//            }
//            else {
//                $this->io->error('File destination already exists');
////            }
//            }

        }


        $this->io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
