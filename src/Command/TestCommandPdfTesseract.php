<?php

namespace App\Command;

use App\Entity\Patient;
use App\Entity\Specialites;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\AbstractString;


define('WORKINGDIR', '/home/pictime/docged/');

class TestCommandPdfTesseract extends Command
{
    protected static $defaultName = 'docged:testTesseract';
    protected static $defaultDescription = 'try to extract first page from a pdf and then use Tesseract to get the text (OCR)';
    private $filesystem;
    public $io;
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->filesystem = new Filesystem();
    }


    /**
     * remove spaces in the filename
     * @return bool
     */
    protected function removeSpace(): bool
    {
        $finder = new Finder();
        $finder->files()->in($_ENV['SCANDIR'])->depth('== 0')->name("*.pdf");

        if (!$finder->hasResults()) {
            $this->io->error("No files found in " . $_ENV['DIRSRC']);
            return false;
        }

        foreach ($finder as $file) {
            $absoluteFilePath = $file->getRealPath();

            if (strpos($absoluteFilePath, ' ') !== false) {
                $new = str_replace(" ", "_", $file->getRealPath());

                if (!$this->filesystem->exists($new)) {
                    $this->filesystem->rename($file->getRealPath(), $new);
                    $absoluteFilePath = $file->getRealPath();
                    $this->io->info($file->getRealPath());
                } else {
                    $this->io->error('File destination already exists');
                }
            }
        }
        unset($absoluteFilePath, $finder);
        return true;

    }


    /**
     * find speciality
     * @param $content
     * @return string
     */
    protected function findSpeciality($content,$listSpecialite): string
    {
        /** @var Specialites $spe */
        foreach($listSpecialite as $spe)
        {
            if(stripos($content,$spe->getCle())!==false)
            {
                return $spe->getValeur();
            }
        }
        return '';
    }


    public function extractFirstDate(Patient $patient, string $content)
    {
        preg_match('/[0-9]{2}\/[0-9]{2}\/[0-9]{2,4}/', $content, $matches, PREG_OFFSET_CAPTURE);
        $bdate = $patient->getBirthdate();

        foreach ($matches as $data) {
            if (is_array($data) && $bdate != $data[0]) {
                    return $data;
            }
        }
        return false;
    }

    /**
     * @param Patient $patient
     * @param string $content
     * @return bool
     */
    public function findPatient(Patient $patient, string $content): bool
    {
        $needles = array();

        $needles[] = $patient->getPrenom() . ' ' . $patient->getNom();
        $needles[] = $patient->getNom() . ' ' . $patient->getPrenom();
        $needles[] = strtoupper($patient->getPrenom() . ' ' . $patient->getNom());
        $needles[] = strtoupper($patient->getNom() . ' ' . $patient->getPrenom());
        $needles[] = $patient->getNom() . ', ' . $patient->getPrenom();
        $needles[] = ucfirst($patient->getNom()) . ' ' . ucfirst($patient->getPrenom());
        $needles[] = $patient->getNom() . ', ' . strtoupper($patient->getPrenom());


        $found = false;
        foreach ($needles as $toto) {
            $x = stripos($content, $toto);
            if (stripos($content, $toto) !== false) {
                $found = true;
            }

        }
        return $found;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = time();

        $this->io = new SymfonyStyle($input, $output);
        $repoPatient = $this->em->getRepository(Patient::class);
        $listPatients = $repoPatient->findAll();
        $repoSpe = $this->em->getRepository(Specialites::class);
        $listeSpe = $repoSpe->findAll();

        $this->removeSpace();

        $finder = new Finder();
        $finder->files()->in($_ENV['SCANDIR'])->depth('== 0')->name("Noir_et*.pdf");

        foreach ($finder as $file) {
            $identifier = rand();
            //2- Extract first page image
            $this->io->info("pdfimages -png -l 1 " . $file->getRealPath() . " tmp/page" . $identifier);
            exec("pdfimages -png -l 1 " . $file->getRealPath() . " tmp/page" . $identifier, $outputPdf, $result_code);

            //3- use Tesseract to get the text from the image
            $this->io->info("tesseract -l fra tmp/page" . $identifier . "-000.png " . $file->getRealPath());
            exec("tesseract -l fra tmp/page" . $identifier . "-000.png " . substr($file->getRealPath(), 0, -4), $outputTesseract, $result_code);


            $dateDoc = '';

            foreach ($listPatients as $patient) {

                if (in_array($patient->getNom() . ' ' . $patient->getPrenom(), ["DELEPIERRE Etienne", 'PLANCOULAINE Thomas'])) {
                    continue;
                }


                $fileContent = file_get_contents(substr($file->getRealPath(), 0, -4) . '.txt');

                if ($this->findPatient($patient, $fileContent) === true) {
                    //let's extract dates from the content.
                    $dateDoc = $this->extractFirstDate($patient, $fileContent);
                    //At this point we have (name, surname, birthdate) let's have a look if we can find the concerned speciality
                    $speciality = $this->findSpeciality($fileContent,$listeSpe);


                    $newfilename = strtoupper($patient->getNom()) . ' ' . ucfirst(strtolower($patient->getPrenom()));
                    if ($dateDoc != false)
                        $newfilename .= ' ' . implode('-', array_reverse(explode('/', $dateDoc[0])));
                    if ($speciality != '')
                        $newfilename .= ' ' . $speciality;
                    $newfilename .= '.pdf';

                    //Ok ready to rename file
                    $path = $file->getPath();
                    $newfilename = $path . '/TO_SEND/' . $newfilename;

                    //Rename PDF file
                    if ($this->filesystem->exists($newfilename))
                        $newfilename = substr($newfilename, 0, -4) . "-" . $identifier . ".pdf";
                    $this->filesystem->rename($file->getRealPath(), $newfilename);

                    //delete txt file
                    $this->filesystem->remove(substr($file->getRealPath(), 0, -4) . '.txt');


                    break;

                }
            }
        }

        //delete temporary images
        $finderDel = new Finder();
        $finderDel->files()->in($_ENV['TMPDIR'])->depth('== 0')->name("*.png");
        foreach ($finderDel as $file) {
            $this->filesystem->remove($file->getRealPath());
        }

        $this->io->success('Processing terminated !');
        $end = time();
        $diff = $end - $start;
        $this->io->success('time elapsed (secs) : ' . $diff);
        return Command::SUCCESS;

    }
}