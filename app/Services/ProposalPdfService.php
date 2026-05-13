<?php

namespace App\Services;

use App\Models\Proposal;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ProposalPdfService
{
    private const PAGE_WIDTH = 595;

    private const PAGE_HEIGHT = 842;

    private const MARGIN = 48;

    public function __construct(private readonly ProposalEventService $events) {}

    public function download(Proposal $proposal): Response
    {
        $pdf = $this->render($proposal);
        $this->events->record($proposal, 'pdf_downloaded', 'PDF da proposta gerado.', $proposal->user);

        $filename = Str::slug($proposal->title ?: 'proposta').'-'.$proposal->id.'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function render(Proposal $proposal): string
    {
        $proposal->loadMissing(['customer', 'items', 'user']);

        $owner = $proposal->user;
        $brandName = $owner->business_name ?: $owner->name;
        $primary = $this->hexToRgb($owner->primary_color ?: '#2563eb');
        $secondary = $this->hexToRgb($owner->secondary_color ?: '#0f172a');
        $dark = [15, 23, 42];
        $muted = [100, 116, 139];
        $light = [241, 245, 249];
        $pages = [];
        $commands = [];
        $y = 0;

        $startPage = function () use (&$commands, &$y, $secondary, $brandName): void {
            $commands = [];
            $y = 724;
            $this->rect($commands, 0, 756, self::PAGE_WIDTH, 86, $secondary);
            $this->text($commands, $brandName, self::MARGIN, 812, 12, 'F2', [255, 255, 255]);
            $this->text($commands, 'Proposta comercial', self::MARGIN, 790, 18, 'F2', [255, 255, 255]);
        };

        $finishPage = function () use (&$pages, &$commands, $owner, $muted): void {
            if ($owner->default_footer_text) {
                $this->text($commands, Str::limit($owner->default_footer_text, 130), self::MARGIN, 34, 8, 'F1', $muted);
            }

            $pages[] = implode("\n", $commands);
        };

        $ensureSpace = function (int $height) use (&$y, &$commands, $startPage, $finishPage): void {
            if ($y - $height >= 72) {
                return;
            }

            $finishPage();
            $startPage();
        };

        $writeBlock = function (string $label, ?string $body) use (&$commands, &$y, $dark, $muted, $ensureSpace): void {
            if (! $body) {
                return;
            }

            $lines = $this->wrap($body, 92);
            $height = 34 + (count($lines) * 13);
            $ensureSpace($height);
            $this->text($commands, $label, self::MARGIN, $y, 11, 'F2', $dark);
            $y -= 18;

            foreach ($lines as $line) {
                $this->text($commands, $line, self::MARGIN, $y, 9, 'F1', $muted);
                $y -= 13;
            }

            $y -= 10;
        };

        $startPage();
        $this->text($commands, $proposal->title, self::MARGIN, $y, 22, 'F2', $dark);
        $y -= 28;

        foreach ($this->wrap((string) $proposal->description, 88) as $line) {
            $this->text($commands, $line, self::MARGIN, $y, 10, 'F1', $muted);
            $y -= 14;
        }

        $this->rect($commands, self::MARGIN, $y - 58, 499, 48, $light);
        $this->text($commands, 'Cliente', self::MARGIN + 16, $y - 25, 8, 'F2', $muted);
        $this->text($commands, $proposal->customer->name, self::MARGIN + 16, $y - 43, 12, 'F2', $dark);
        $this->text($commands, 'Validade', 372, $y - 25, 8, 'F2', $muted);
        $this->text($commands, $proposal->valid_until?->format('d/m/Y') ?? 'Sem validade definida', 372, $y - 43, 10, 'F2', $dark);
        $y -= 86;

        $ensureSpace(48);
        $this->text($commands, 'Itens', self::MARGIN, $y, 14, 'F2', $dark);
        $y -= 22;
        $this->rect($commands, self::MARGIN, $y - 18, 499, 24, $secondary);
        $this->text($commands, 'Descricao', self::MARGIN + 10, $y - 10, 8, 'F2', [255, 255, 255]);
        $this->text($commands, 'Qtd.', 330, $y - 10, 8, 'F2', [255, 255, 255]);
        $this->text($commands, 'Unitario', 386, $y - 10, 8, 'F2', [255, 255, 255]);
        $this->text($commands, 'Total', 468, $y - 10, 8, 'F2', [255, 255, 255]);
        $y -= 30;

        foreach ($proposal->items as $item) {
            $lines = $this->wrap($item->description, 48);
            $rowHeight = max(26, 12 + (count($lines) * 12));
            $ensureSpace($rowHeight + 8);
            $this->rect($commands, self::MARGIN, $y - $rowHeight + 8, 499, $rowHeight, [248, 250, 252]);
            $lineY = $y - 8;

            foreach ($lines as $line) {
                $this->text($commands, $line, self::MARGIN + 10, $lineY, 9, 'F1', $dark);
                $lineY -= 12;
            }

            $this->text($commands, number_format((float) $item->quantity, 2, ',', '.'), 330, $y - 8, 9, 'F1', $dark);
            $this->text($commands, $this->money($item->unit_price), 386, $y - 8, 9, 'F1', $dark);
            $this->text($commands, $this->money($item->total), 468, $y - 8, 9, 'F2', $dark);
            $y -= $rowHeight + 4;
        }

        $ensureSpace(96);
        $summaryX = 346;
        $this->text($commands, 'Subtotal', $summaryX, $y - 4, 9, 'F1', $muted);
        $this->text($commands, $this->money($proposal->subtotal), 454, $y - 4, 9, 'F2', $dark);
        $this->text($commands, 'Desconto', $summaryX, $y - 24, 9, 'F1', $muted);
        $this->text($commands, $this->money($proposal->discount), 454, $y - 24, 9, 'F2', $dark);
        $this->rect($commands, $summaryX, $y - 78, 201, 36, $primary);
        $this->text($commands, 'Total', $summaryX + 12, $y - 56, 10, 'F2', [255, 255, 255]);
        $this->text($commands, $this->money($proposal->total), 454, $y - 56, 12, 'F2', [255, 255, 255]);
        $y -= 104;

        $writeBlock('Condicoes comerciais', $proposal->commercial_terms);
        $writeBlock('Observacoes', $proposal->notes);
        $writeBlock('Contato', $owner->contact_details);
        $finishPage();

        return $this->buildPdf($pages);
    }

    private function buildPdf(array $pages): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $pageIds = [];
        $nextId = 4;

        foreach ($pages as $content) {
            $contentId = ++$nextId;
            $objects[$contentId] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
            $pageId = ++$nextId;
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $pageIds[] = $pageId.' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageIds).'] /Count '.count($pageIds).' >>';

        $pdf = "%PDF-1.4\n%".chr(226).chr(227).chr(207).chr(211)."\n";
        $offsets = [0 => 0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$object."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$id])."\n";
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";
    }

    private function rect(array &$commands, float $x, float $y, float $width, float $height, array $color): void
    {
        $commands[] = $this->rgb($color).' rg '.sprintf('%.2F %.2F %.2F %.2F re f', $x, $y, $width, $height);
    }

    private function text(array &$commands, string $text, float $x, float $y, int $size, string $font, array $color): void
    {
        $commands[] = 'BT /'.$font.' '.$size.' Tf '.$this->rgb($color).' rg 1 0 0 1 '.sprintf('%.2F %.2F', $x, $y).' Tm ('.$this->escapeText($text).') Tj ET';
    }

    private function wrap(string $text, int $length): array
    {
        if (trim($text) === '') {
            return [];
        }

        $lines = [];

        foreach (preg_split('/\R/', trim($text)) ?: [] as $paragraph) {
            $lines = array_merge($lines, explode("\n", wordwrap($paragraph, $length, "\n", true)));
        }

        return array_values(array_filter($lines, fn (string $line): bool => trim($line) !== ''));
    }

    private function escapeText(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        $text = $converted === false ? preg_replace('/[^\x20-\x7E]/', '?', $text) : $converted;

        return str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], $text);
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return [37, 99, 235];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function rgb(array $color): string
    {
        return sprintf('%.3F %.3F %.3F', $color[0] / 255, $color[1] / 255, $color[2] / 255);
    }

    private function money(float|string $value): string
    {
        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }
}
