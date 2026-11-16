# measure_pages.ps1 - report the printed page number of every Heading 1-3.
#
# The portfolio carries a static table of contents, so the page numbers have to
# be measured from a rendered document and fed back into the Markdown source.
# Page numbers are returned as printed (the cover section is not counted, which
# is why 1 is subtracted from Word's absolute page number).
#
# Usage: powershell -File measure_pages.ps1 -Docx <path> -Out <json path>

param(
    [Parameter(Mandatory = $true)][string]$Docx,
    [Parameter(Mandatory = $true)][string]$Out
)

$ErrorActionPreference = "Stop"

$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
$document = $null
try {
    $document = $word.Documents.Open($Docx, $false, $true)
    $document.ActiveWindow.View.Type = 3      # print layout, so pagination is real
    $document.Repaginate()

    $rows = New-Object System.Collections.Generic.List[object]
    foreach ($paragraph in $document.Paragraphs) {
        $level = [int]$paragraph.OutlineLevel
        if ($level -ge 1 -and $level -le 3) {
            $text = $paragraph.Range.Text.Trim([char]13, [char]10, [char]7, [char]32)
            $page = [int]$paragraph.Range.Information(3) - 1
            $rows.Add([pscustomobject]@{ level = $level; text = $text; page = $page })
        }
    }
    $rows | ConvertTo-Json -Depth 3 | Set-Content -Encoding utf8 -Path $Out
    Write-Output ("headings: " + $rows.Count + " ; pages: " + $document.ComputeStatistics(2))
}
finally {
    if ($document) { $document.Close(0) }
    $word.Quit()
}
