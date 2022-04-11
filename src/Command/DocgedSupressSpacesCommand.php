<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DocgedSupressSpacesCommand extends Command
{
    protected static $defaultName = 'docged:supressSpaces';
    protected static $defaultDescription = 'Replace spaces in filenames with underscores';
    private $filesystem;
    public $io;

    protected function configure(): void
    {
        $this->filesystem = new Filesystem();

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $finder = new Finder();
        $finder->files()->in($_ENV['DIRSRC'])->depth('== 0');
        if(!$finder->hasResults())
        {
            $this->io->error("No files found in "+$_ENV['DIRSRC']);
            return Command::FAILURE;
        }

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();
            $fileNameWithExtension = $file->getRelativePathname();
            $this->io->info("-------------");
            $this->io->info("ABS  : ".$absoluteFilePath);

            $new = str_replace(" ","_",$file->getRealPath());

            if (!$this->filesystem->exists($new)) {
                $this->filesystem->rename($file->getRealPath(), $new);
                $this->io->info($file->getRealPath());
            }
            else {
                $this->io->error('File destination already exists');
            }

        }


        $this->io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
