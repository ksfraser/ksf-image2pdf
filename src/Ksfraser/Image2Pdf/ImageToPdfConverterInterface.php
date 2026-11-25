<?php
declare(strict_types=1);

namespace Ksfraser\Image2Pdf;

/**
 * Result of an image-to-PDF conversion.
 */
class PdfResult
{
    /** @var string Path to the produced PDF file (empty on failure) */
    public $pdfPath = '';
    /** @var bool Whether conversion succeeded */
    public $success = false;
    /** @var string Error message when success === false */
    public $error = '';
    /** @var array Source image paths that were converted */
    public $sources = [];
    /** @var int Output file size in bytes */
    public $fileSize = 0;

    public function __construct(array $sources = [])
    {
        $this->sources = $sources;
    }
}

/**
 * Interface for converting one or more images to a single PDF.
 *
 * Implementations may shell out to ImageMagick (`convert`/`magick`),
 * `img2pdf`, TCPDF, etc. Keep the implementation free of FA specifics so it
 * can be unit-tested standalone.
 */
interface ImageToPdfConverterInterface
{
    /**
     * Convert images to a single PDF.
     *
     * @param string[] $imagePaths Paths to source images (jpg/png)
     * @param string   $outputPath Destination PDF path
     * @return PdfResult
     */
    public function convert(array $imagePaths, string $outputPath): PdfResult;
}
