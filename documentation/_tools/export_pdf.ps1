# export_pdf.ps1 - export a portfolio DOCX to PDF with Word.
#
# The document must be opened in print layout before exporting: Word otherwise
# keeps it in Web Layout, where pagination is undefined and the export can take
# minutes. The portfolio carries a static table of contents (no TOC field and no
# "update fields on open"), which is what makes this export deterministic.
#
# Usage:
#   powershell -NoProfile -File export_pdf.ps1 `
#       -Docx documentation\Portfolio\ISU_OSAS_Hub_System_Development_Portfolio.docx

param(
    [Parameter(Mandatory = $true)][string]$Docx,
    [string]$Pdf = ""
)

$ErrorActionPreference = "Stop"

$Docx = (Resolve-Path $Docx).Path
if (-not $Pdf) { $Pdf = [IO.Path]::ChangeExtension($Docx, ".pdf") }

$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
$document = $null
try {
    $document = $word.Documents.Open($Docx, $false, $true)
    $document.ActiveWindow.View.Type = 3      # print layout
    $document.Repaginate()

    $started = Get-Date
    $document.ExportAsFixedFormat($Pdf, 17)   # 17 = wdExportFormatPDF
    $seconds = [math]::Round(((Get-Date) - $started).TotalSeconds, 1)

    Write-Output ("pages: " + $document.ComputeStatistics(2))
    Write-Output ("export: " + $seconds + " s")
    Write-Output ("pdf: " + $Pdf + " (" + (Get-Item $Pdf).Length + " bytes)")
}
finally {
    if ($document) { $document.Close(0) }
    $word.Quit()
}
