<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="same-origin">
    <title>{{ $reference }} - PDF Preview</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font: 14px/1.5 system-ui, sans-serif; color: #17212b; background: #e9edef; }
        header { position: sticky; top: 0; z-index: 1; display: flex; align-items: center; flex-wrap: wrap; gap: 12px; padding: 12px 20px; background: white; border-bottom: 1px solid #d6dde2; }
        h1 { font-size: 16px; margin: 0; flex: 1 1 180px; overflow-wrap: anywhere; }
        .controls { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        button, select, a { font: inherit; border: 1px solid #c6d1d8; border-radius: 6px; background: white; color: inherit; min-height: 38px; padding: 7px 10px; }
        button { width: 38px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        button:disabled { opacity: .4; cursor: default; }
        button svg, a svg { width: 18px; height: 18px; }
        a { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #087f78; color: white; border-color: #087f78; }
        :focus-visible { outline: 3px solid #12b8aa; outline-offset: 2px; }
        #page-count { min-width: 72px; text-align: center; font-variant-numeric: tabular-nums; }
        #status { padding: 12px 20px; margin: 0; text-align: center; background: #fff; }
        #status[hidden] { display: none; }
        #document { padding: 20px; overflow: auto; min-height: calc(100vh - 80px); }
        canvas { display: block; margin: 0 auto; background: white; box-shadow: 0 2px 8px #0002; }
        canvas[hidden] { display: none; }
        @media (max-width: 600px) { header { padding: 10px 12px; } #document { padding: 12px; } h1 { flex-basis: 100%; } }
    </style>
    @vite('resources/js/pdf-viewer.js')
</head>
<body data-pdf-url="{{ $pdfUrl }}">
    <header>
        <h1>{{ $reference }} <span style="font-weight: 400;">/ PDF Preview</span></h1>
        <div class="controls" aria-label="PDF controls">
            <button id="previous" title="Previous page" aria-label="Previous page" disabled><x-heroicon-o-chevron-left /></button>
            <span id="page-count" aria-live="polite">0 / 0</span>
            <button id="next" title="Next page" aria-label="Next page" disabled><x-heroicon-o-chevron-right /></button>
            <select id="zoom" aria-label="Zoom" disabled>
                <option value="fit">Fit width</option>
                <option value="0.75">75%</option>
                <option value="1">100%</option>
                <option value="1.25">125%</option>
                <option value="1.5">150%</option>
                <option value="2">200%</option>
            </select>
            <a href="{{ $downloadUrl }}"><x-heroicon-o-arrow-down-tray />Download PDF</a>
        </div>
    </header>
    <p id="status" role="status">Loading PDF. If the preview is unavailable, use Download PDF.</p>
    <noscript><p>JavaScript is required for preview. You can still download the PDF above.</p></noscript>
    <main id="document" aria-label="PDF document" aria-busy="true">
        <canvas id="pdf-page" role="img" aria-label="PDF page" hidden></canvas>
    </main>
</body>
</html>
