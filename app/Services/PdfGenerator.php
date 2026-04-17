<?php

namespace App\Services;

use App\Models\PdfTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfGenerator
{
    private $pdfFolderDirectory;

    private $generated_file_name;

    public function __construct()
    {
        $this->pdfFolderDirectory = 'pdf_templates';
    }

    /**
     * Generate a PDF from a Blade template.
     *
     * @param  mixed  $paperSize
     * @param  string  $action  Supported values are 'download', 'stream', and 'save'.
     * @param  string  $file_path  The path to save the generated PDF file. If empty, the PDF is sent to the browser.
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \InvalidArgumentException
     */
    public function generatePdfFromTemplate(object|array $data, PdfTemplate $pdfTemplate, $paperSize, string $action, $file_path = null)
    {
        $pdf = null;
        $lastException = null;

        foreach ($this->templatesToTry($pdfTemplate) as $template) {
            try {
                $pdf = $this->loadPdfFromTemplate($template, $data);
                break;
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        if ($pdf === null) {
            if ($lastException) {
                report($lastException);
            }
            $detail = $lastException ? ' '.$lastException->getMessage() : '';
            throw new \RuntimeException(
                'Could not render PDF: no working template for type '.($pdfTemplate->type ?? 'unknown').'.'.$detail,
                0,
                $lastException
            );
        }

        $paperSize = is_array($paperSize)
            ? $this->paperSize(...$paperSize)
            : $paperSize;

        $pdf->setPaper($paperSize, 'portrait');

        $file_name = $this->generated_file_name ?? $this->getNameOfDownloadedPdf($pdfTemplate->name);

        return match ($action) {
            'download' => $pdf->download($file_name),
            'stream' => $pdf->stream($file_name),
            'save' => $pdf->save($file_name),
            default => throw new \InvalidArgumentException("Invalid action: {$action}"),
        };
    }

    /**
     * Resolve template from DB: admin uploads live on the default disk (e.g. public/pdf_templates/*.blade.php);
     * bundled templates live under resources/views (e.g. pdf_templates/default_order_invoice.blade.php).
     * Never treat human-readable "name" as a view segment — only .blade.php paths.
     */
    private function loadPdfFromTemplate(PdfTemplate $template, object|array $data): mixed
    {
        $viewData = compact('data');
        $path = trim((string) ($template->path ?? ''));
        $fileName = trim((string) ($template->file_name ?? ''));

        $bladeRelativePaths = [];

        if ($path !== '' && str_ends_with(strtolower($path), '.blade.php')) {
            $bladeRelativePaths[] = str_replace('\\', '/', $path);
        }

        if ($path !== '' && $fileName !== '' && str_ends_with(strtolower($fileName), '.blade.php')) {
            $bladeRelativePaths[] = rtrim(str_replace('\\', '/', $path), '/').'/'.$fileName;
        }

        $bladeRelativePaths = array_values(array_unique(array_filter($bladeRelativePaths)));

        foreach ($bladeRelativePaths as $relative) {
            if (Storage::exists($relative)) {
                $absolute = Storage::path($relative);
                if (is_readable($absolute)) {
                    $html = view()->file($absolute, $viewData)->render();

                    return Pdf::loadHTML($this->sanitizeDompdfHtmlWebpImgSources($html));
                }
            }
        }

        foreach ($bladeRelativePaths as $relative) {
            $full = resource_path('views'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));
            if (is_file($full)) {
                $viewName = str_replace('/', '.', preg_replace('/\.blade\.php$/i', '', $relative));
                $html = view($viewName, $viewData)->render();

                return Pdf::loadHTML($this->sanitizeDompdfHtmlWebpImgSources($html));
            }
        }

        if ($path !== '' && str_ends_with(strtolower($path), '.blade.php')) {
            $relative = str_replace('\\', '/', $path);
            $viewName = str_replace('/', '.', preg_replace('/\.blade\.php$/i', '', $relative));
            $html = view($viewName, $viewData)->render();

            return Pdf::loadHTML($this->sanitizeDompdfHtmlWebpImgSources($html));
        }

        throw new \InvalidArgumentException(
            'PDF template row is invalid: path and file_name must resolve to a .blade.php file. '
            .'path=['.$path.'] file_name=['.$fileName.']. '
            .'Fix the template in Admin or set the shop invoice template to a valid default.'
        );
    }

    /**
     * DomPDF uses GD; WEBP needs a working imagecreatefromwebp + WebP Support in gd_info().
     * Replace any img src ending in .webp (incl. file://) so payment HTML / custom blades cannot crash PDFs.
     */
    private function sanitizeDompdfHtmlWebpImgSources(string $html): string
    {
        if (function_exists('php_gd_can_read_webp') && php_gd_can_read_webp()) {
            return $html;
        }

        $pixel = function_exists('pdf_dompdf_transparent_pixel_data_uri')
            ? pdf_dompdf_transparent_pixel_data_uri()
            : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

        $out = preg_replace_callback(
            '/\ssrc\s*=\s*([\'"])([^\'"]*\.webp)\1/i',
            function (array $m) use ($pixel) {
                return ' src='.$m[1].$pixel.$m[1];
            },
            $html
        );

        return is_string($out) ? $out : $html;
    }

    /**
     * @return list<PdfTemplate>
     */
    private function templatesToTry(PdfTemplate $pdfTemplate): array
    {
        // Do not require active=1: mis-flagged rows would block fallbacks; try all rows for this type.
        $ordered = collect([$pdfTemplate])->merge(
            PdfTemplate::where('type', $pdfTemplate->type)
                ->orderByDesc('is_default')
                ->orderByDesc('active')
                ->orderBy('id')
                ->get()
        );

        return $ordered->unique('id')->values()->all();
    }

    /**
     * Returns the paper size in points, given a width and height in any unit.
     *
     * @param  string  $scale  The unit of measurement for the width and height. Supported values are 'mm', 'cm', 'inch'.
     */
    private function paperSize(float $width, float $height, string $scale = 'inch'): array
    {
        $pointsPerUnit = $this->getPointsPerUnit($scale);

        $paperWidth = $width * $pointsPerUnit;
        $paperHeight = $height * $pointsPerUnit;

        return [0, 0, $paperWidth, $paperHeight];
    }

    /**
     * Returns the number of points per unit for the given unit of measurement.
     * In DomPDF package every inch equals to 72 points in screen size.
     *
     * @param  string  $unit  The unit of measurement. Supported values are 'mm', 'cm', 'inch'.
     */
    private function getPointsPerUnit(string $unit): float
    {
        return match ($unit) {
            'mm' => 72 / 25.4,
            'cm' => 72 / 2.54,
            'inch' => 72,
            default => 72,
        };
    }

    /**
     * Return the name of the generated PDF file. The name is the given file name
     * with '.pdf' appended.
     *
     * @param  string  $fileName  The name of the generated PDF file.
     * @return string The name of the generated PDF file.
     */
    public function getNameOfDownloadedPdf($fileName)
    {
        return "{$fileName}.pdf";
    }

    /**
     * Set the name of the generated PDF file.
     *
     * @param  string  $fileName
     * @return $this
     */
    public function setGeneratedFileName($fileName)
    {
        $this->generated_file_name = Str::endsWith($fileName, '.pdf') ? $fileName : "{$fileName}.pdf";

        return $this;
    }
}
