<?php

namespace mattstein\dekindler;

use JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ExtractCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected static $defaultName = 'extract';

    /**
     * @inheritdoc
     */
    protected static $defaultDescription = 'Extracts Kindle clippings.';

    /**
     * @var string Full path of the Kindle’s source text file to read and parse
     */
    public string $sourceFilePath = '/Volumes/Kindle/documents/My Clippings.txt';

    /**
     * @var ?string
     */
    public ?string $format = null;

    /**
     * @var ?string
     */
    public ?string $outputDir = null;

    /**
     * @var ?bool
     */
    public ?bool $overwrite = null;

    /**
     * @var ?bool
     */
    public ?bool $webSafeFilenames = null;

    /**
     * @var bool
     */
    public bool $omitHighlights = false;

    /**
     * @var bool
     */
    public bool $omitNotes = false;

    /**
     * @var bool
     */
    public bool $omitBookmarks = true;

    /**
     * @var bool
     */
    public bool $removeDuplicates = true;

    /**
     * @var ?string
     */
    public ?string $jsonFilename = null;

    /**
     * @var Extractor
     */
    private Extractor $extractor;

    /**
     * @var Writer
     */
    private Writer $writer;

    public function __construct()
    {
        $this->extractor = new Extractor();
        $this->writer = new Writer();

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->addArgument('outputDir', InputArgument::OPTIONAL, 'Output directory')
            ->addOption('sourceFilePath', 's', InputOption::VALUE_OPTIONAL, 'Source clipping file', $this->sourceFilePath)
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format', $this->writer->outputFormat)
            ->addOption('overwrite', 'o', InputOption::VALUE_OPTIONAL, 'Overwrite existing file(s)?', $this->writer->overwrite)
            ->addOption('webSafeFilenames', 'w', InputOption::VALUE_OPTIONAL, 'Use web-friendly Markdown filenames?', $this->writer->webSafeFilenames)
            ->addOption('jsonFilename', 'j', InputOption::VALUE_OPTIONAL, 'JSON output filename', $this->writer->jsonFilename)
            ->addOption('omitHighlights', 'oh', InputOption::VALUE_OPTIONAL, 'Omit highlights?', $this->omitHighlights)
            ->addOption('omitNotes', 'on', InputOption::VALUE_OPTIONAL, 'Omit notes?', $this->omitNotes)
            ->addOption('omitBookmarks', 'ob', InputOption::VALUE_OPTIONAL, 'Omit bookmarks?', $this->omitBookmarks)
            ->addOption('removeDuplicates', 'd', InputOption::VALUE_OPTIONAL, 'Remove duplicates?', $this->removeDuplicates)
        ;
    }

    /**
     * @inheritdoc
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $collectibleTypes = [];

        $rules = [
            'sourceFilePath',
            'omitHighlights' => ['type' => 'boolean'],
            'omitNotes' => ['type' => 'boolean'],
            'omitBookmarks' => ['type' => 'boolean'],
            'removeDuplicates' => ['type' => 'boolean'],
            'format',
            'overwrite' => ['type' => 'boolean'],
            'jsonFilename',
            'webSafeFilenames' => ['type' => 'boolean'],
        ];

        if (!$this->validateInput($input, $output, $rules)) {
            return Command::INVALID;
        }

        if (!is_file($this->sourceFilePath)) {
            $output->writeln('<error>Cannot read ' . $this->sourceFilePath . '.</error>');
            return Command::INVALID;
        }

        $output->writeln('<info>Reading `' . $this->sourceFilePath . '`.</info>');

        $sourceText = file_get_contents($this->sourceFilePath);

        if (! $this->omitHighlights) {
            $collectibleTypes[] = KindleClipping::TYPE_HIGHLIGHT;
        }

        if (! $this->omitNotes) {
            $collectibleTypes[] = KindleClipping::TYPE_NOTE;
        }

        if (! $this->omitBookmarks) {
            $collectibleTypes[] = KindleClipping::TYPE_BOOKMARK;
        }

        $this->extractor->parse($sourceText, $collectibleTypes, $this->removeDuplicates);
        $clippingsByBook = $this->extractor->getClippingsByBook();

        $output->writeln(
            sprintf('<info>Found %d books with %d highlights, %d notes, and %d bookmarks.</info>',
                count($clippingsByBook),
                $this->extractor->highlightCount,
                $this->extractor->noteCount,
                $this->extractor->bookmarkCount
            )
        );

        foreach ($clippingsByBook as $clippings) {
            $firstClipping = $clippings[0];
            $output->writeln($firstClipping->title . ' (' . count($clippings) . ' clippings)');
        }

        if ($this->format) {
            $this->writer->outputFormat = $this->format;
        }

        if ($outputDir = $input->getArgument('outputDir')) {
            $this->writer->outputDir = $outputDir;
        } else {
			$output->writeln('<error>outputDir cannot be empty.</error>');
			return Command::INVALID;
		}


        if ($this->overwrite) {
            $this->writer->overwrite = $this->overwrite;
        }

        if ($this->jsonFilename) {
            $this->writer->jsonFilename = $this->jsonFilename;
        }

        if (is_bool($this->webSafeFilenames)) {
            $this->writer->webSafeFilenames = $this->webSafeFilenames;
        }

        $this->writer->setExtractor($this->extractor);
        $this->writer->write($collectibleTypes);

        $warnings = $this->writer->getWarnings();

        foreach ($warnings as $warning) {
            $output->writeln('<fg=#888888>' . $warning . '</>');
        }

        $written = $this->writer->getFilesWritten();

        foreach ($written as $file) {
            $output->writeln('<info>Saved `' . $file . '`.</info>');
        }

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $rules
     * @return bool
     */
    private function validateInput(InputInterface $input, OutputInterface $output, array $rules): bool
    {
        foreach ($rules as $rule => $settings) {

            if (is_numeric($rule)) {
                $rule = $settings;
                $settings = null;
            }

            if (isset($settings)) {
                if (isset($settings['type'])) {
                    $type = $settings['type'];

                    if (($type === 'boolean') && $param = $input->getOption($rule)) {
						$originalParam = $param;
						$param = filter_var($param, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
						if (!is_bool($param)) {
							$this->invalidOption($output, $rule, $originalParam);
							return false;
						}
						$this->{$rule} = $param;
					}
                }
            } else if ($param = $input->getOption($rule)) {
				$this->{$rule} = $param;
			}
        }

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param string $optionName
     * @param string $optionValue
     * @return int
     */
    private function invalidOption(OutputInterface $output, string $optionName, string $optionValue): int
    {
        $output->writeLn('<error>Invalid option `' . $optionValue .  '` supplied for ' . $optionName . '.</error>');
        return Command::INVALID;
    }
}
