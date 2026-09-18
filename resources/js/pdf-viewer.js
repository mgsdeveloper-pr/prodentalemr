import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

GlobalWorkerOptions.workerSrc = workerUrl;

const container = document.getElementById('document');
const canvas = document.getElementById('pdf-page');
const status = document.getElementById('status');
const previous = document.getElementById('previous');
const next = document.getElementById('next');
const zoom = document.getElementById('zoom');
const count = document.getElementById('page-count');
let pdf;
let pageNumber = 1;
let rendering = false;
let pending = false;

function showError() {
    canvas.hidden = true;
    status.hidden = false;
    status.textContent = 'The preview could not be loaded. Try reloading this page or use Download PDF. If your session expired, sign in again.';
    container.setAttribute('aria-busy', 'false');
}

async function renderPage() {
    if (!pdf) return;
    if (rendering) { pending = true; return; }
    rendering = true;
    container.setAttribute('aria-busy', 'true');
    previous.disabled = true;
    next.disabled = true;
    try {
        const page = await pdf.getPage(pageNumber);
        const base = page.getViewport({ scale: 1 });
        const padding = parseFloat(getComputedStyle(container).paddingLeft) * 2;
        const scale = zoom.value === 'fit'
            ? Math.max(0.1, (container.clientWidth - padding) / base.width)
            : Number(zoom.value);
        const viewport = page.getViewport({ scale });
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.ceil(viewport.width * ratio);
        canvas.height = Math.ceil(viewport.height * ratio);
        canvas.style.width = `${viewport.width}px`;
        canvas.style.height = `${viewport.height}px`;
        await page.render({ canvasContext: canvas.getContext('2d'), viewport, transform: [ratio, 0, 0, ratio, 0, 0] }).promise;
        canvas.hidden = false;
        canvas.setAttribute('aria-label', `PDF page ${pageNumber} of ${pdf.numPages}`);
        count.textContent = `${pageNumber} / ${pdf.numPages}`;
        status.hidden = true;
    } catch {
        showError();
    } finally {
        rendering = false;
        container.setAttribute('aria-busy', 'false');
        previous.disabled = pageNumber <= 1;
        next.disabled = pageNumber >= pdf.numPages;
        if (pending) { pending = false; renderPage(); }
    }
}

previous.addEventListener('click', () => { if (pageNumber > 1) { pageNumber--; renderPage(); } });
next.addEventListener('click', () => { if (pdf && pageNumber < pdf.numPages) { pageNumber++; renderPage(); } });
zoom.addEventListener('change', renderPage);
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => { if (zoom.value === 'fit') renderPage(); }, 150);
});

try {
    // Fetch only from the authenticated application; no external PDF service receives patient data.
    const response = await fetch(document.body.dataset.pdfUrl, { credentials: 'same-origin', cache: 'no-store' });
    if (!response.ok || !response.headers.get('content-type')?.includes('application/pdf')) throw new Error('PDF unavailable');
    pdf = await getDocument({ data: new Uint8Array(await response.arrayBuffer()), isEvalSupported: false }).promise;
    zoom.disabled = false;
    await renderPage();
} catch {
    showError();
}
