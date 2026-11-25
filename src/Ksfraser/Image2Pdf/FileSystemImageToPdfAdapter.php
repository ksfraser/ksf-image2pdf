<?php
declare(strict_types=1);

namespace Ksfraser\Image2Pdf;

/**
 * Image-to-PDF converter that shells out to ImageMagick or img2pdf.
 *
 * Command resolution order:
 *   1. `magick`      (ImageMagick 7)
 *   2. `convert`     (ImageMagick 6)
 *   3. `img2pdf`     (standalone img2pdf tool)
 *
 * Set the `KSF_IMAGE2PDF_CONVERTER_CMD` env var to force a specific command.
 */
class FileSystemImageToPdfAdapter implements ImageToPdfConverterInterface
{
    /** @var string|null Forced converter command (from env) */
    private $forcedCommand;

    public function __construct()
    {
        $this->forcedCommand = getenv('KSF_IMAGE2PDF_CONVERTER_CMD');
        if ($this->forcedCommand === false || $this->forcedCommand === '') {
            $this->forcedCommand = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function convert(array $imagePaths, string $outputPath): PdfResult
    {
        $result = new PdfResult($imagePaths);

        if (empty($imagePaths)) {
            $result->error = 'No source images provided';
            return $result;
        }

        $cmd = $this->resolveCommand();
        if ($cmd === null) {
            $result->error = 'No image-to-PDF converter found (tried magick, convert, img2pdf). Set KSF_IMAGE2PDF_CONVERTER_CMD.';
            return $result;
        }

        $command = $this->buildCommand($cmd, $imagePaths, $outputPath);
        $output = [];
        $exitCode = null;
        exec($command . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            $result->error = implode("\n", $output);
            return $result;
        }

        if (!file_exists($outputPath)) {
            $result->error = 'Converter exited 0 but output PDF was not created at ' . $outputPath;
            return $result;
        }

        $result->success = true;
        $result->pdfPath = $outputPath;
        $result->fileSize = (int) filesize($outputPath);
        return $result;
    }

    /**
     * Resolve the first available converter command.
     *
     * @return string|null Command basename, or null
     */
    private function resolveCommand(): ?string
    {
        if ($this->forcedCommand !== null) {
            return $this->forcedCommand;
        }

        foreach (['magick', 'convert', 'img2pdf'] as $cmd) {
            if ($this->commandExists($cmd)) {
                return $cmd;
            }
        }

        return null;
    }

    private function commandExists(string $cmd): bool
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $check = 'where ' . escapeshellarg($cmd);
        } else {
            $check = 'command -v ' . escapeshellarg($cmd);
        }
        exec($check . ' 2>&1', $out, $code);
        return $code === 0 && !empty($out);
    }

    /**
     * Build the shell command for the chosen converter.
     *
     * @return string
     */
    private function buildCommand(string $cmd, array $imagePaths, string $outputPath): string
    {
        $outArg = escapeshellarg($outputPath);
        $imgArgs = implode(' ', array_map('escapeshellarg', $imagePaths));

        if ($cmd === 'img2pdf') {
            return "img2pdf $imgArgs -o $outArg";
        }

        // ImageMagick: magick/convert <inputs> <output>
        return "$cmd $imgArgs $outArg";
    }
}
