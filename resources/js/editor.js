import {confirmAction} from './dialogs';
import {PixelDocument, render, pngBlob} from './pixels';
import {ArtworkSync, copyLink} from './sync';
export function startEditor(root) {
    const config = JSON.parse(root.dataset.config);
    let model = new PixelDocument(config.size, config.pixels);
    const $ = id => document.getElementById(id);
    const canvas = $('canvas'), name = $('artwork-name'), picker = $('color-picker');
    let tool = 'pencil', color = picker.value, dragging = false, last = null, keyboard = 0;
    let unsaved = false, nameDirty = false;
    const status = message => { $('sync-status').textContent = message; };
    const refresh = () => {
        render(canvas, model.size, model.pixels, $('show-grid').checked);
        render($('preview'), model.size, model.pixels);
        $('undo').disabled = !config.editable || !model.undoStack.length;
        $('redo').disabled = !config.editable || !model.redoStack.length;
        $('canvas-label').textContent = `KANVAS / ${model.size} × ${model.size}`;
    };
    const sync = new ArtworkSync(config, model, root.dataset.storeUrl, status, refresh, () => dragging);
    const changed = changes => {
        if (changes.length) { sync.queue(changes); unsaved = true; status('Perubahan belum disimpan'); }
        refresh();
    };
    const chooseTool = value => {
        tool = value;
        root.querySelectorAll('[data-tool]').forEach(button => {
            const active = button.dataset.tool === value;
            button.classList.toggle('active', active); button.setAttribute('aria-pressed', String(active));
        });
    };
    root.querySelectorAll('[data-tool]').forEach(button => button.addEventListener('click', () => chooseTool(button.dataset.tool)));
    const setColor = value => { color = value; picker.value = value; $('color-value').textContent = value.toUpperCase(); };
    ['#111111','#ffffff','#214db3','#6d89ca','#e85b45','#e9ad48','#eee08c','#689e75','#365d4e','#9565a5','#dc99af','#9d7456'].forEach(value => {
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'swatch'; button.style.backgroundColor = value;
        button.setAttribute('aria-label', `Pilih warna ${value}`); button.disabled = !config.editable;
        button.addEventListener('click', () => setColor(value)); root.querySelector('.palette').append(button);
    });
    picker.addEventListener('input', () => setColor(picker.value));
    const position = event => {
        const rect = canvas.getBoundingClientRect();
        const x = Math.min(model.size - 1, Math.max(0, Math.floor((event.clientX - rect.left) / rect.width * model.size)));
        const y = Math.min(model.size - 1, Math.max(0, Math.floor((event.clientY - rect.top) / rect.height * model.size)));
        $('coordinates').textContent = `X ${String(x + 1).padStart(2, '0')} / Y ${String(y + 1).padStart(2, '0')}`;
        return y * model.size + x;
    };
    canvas.addEventListener('pointerdown', event => {
        if (!config.editable || event.button !== 0) return;
        event.preventDefault(); canvas.focus(); canvas.setPointerCapture(event.pointerId);
        dragging = true; last = position(event); model.begin();
        if (tool === 'fill') { model.fill(last, color); } else { model.paint(last, tool === 'eraser' ? null : color); }
        refresh();
    });
    canvas.addEventListener('pointermove', event => {
        const index = position(event);
        if (!dragging || tool === 'fill') return;
        model.line(last, index, tool === 'eraser' ? null : color); last = index; refresh();
    });
    const finish = () => { if (dragging) { dragging = false; changed(model.end()); last = null; } };
    ['pointerup','pointercancel','lostpointercapture'].forEach(type => canvas.addEventListener(type, finish));
    $('show-grid').addEventListener('change', refresh);
    const undo = () => { if (!dragging && config.editable) changed(model.undo()); };
    const redo = () => { if (!dragging && config.editable) changed(model.redo()); };
    $('undo').addEventListener('click', undo); $('redo').addEventListener('click', redo);
    $('clear').addEventListener('click', async () => { if (await confirmAction('Bersihkan seluruh kanvas? Anda dapat mengurungkannya.')) changed(model.clear()); });
    $('grid-size').addEventListener('change', async event => {
        if (sync.busy || sync.polling || !(await confirmAction('Ganti ukuran dan mulai kanvas baru? Simpan atau unduh karya sebelumnya terlebih dahulu.'))) { event.target.value = model.size; return; }
        model = new PixelDocument(Number(event.target.value)); sync.document = model;
        Object.assign(config, {saved:false, readUrl:null, writeUrl:null, viewUrl:null, editUrl:null});
        sync.pending.clear(); sync.version = 0; name.value = 'Tanpa judul'; unsaved = false; nameDirty = false;
        history.replaceState(null, '', '/'); status('Kanvas baru · belum disimpan'); refresh();
    });
    name.addEventListener('input', () => { nameDirty = true; });
    const save = async () => {
        const value = name.value.trim();
        if (!value) { status('Beri nama karya terlebih dahulu.'); name.focus(); return false; }
        const result = await sync.save(value, true);
        if (result) { unsaved = false; nameDirty = false; }
        return result;
    };
    $('save')?.addEventListener('click', save);
    const share = async type => {
        if (!config.saved && !(await save())) return;
        const url = config[type === 'view' ? 'viewUrl' : 'editUrl'];
        if (url) await copyLink(url, status);
    };
    $('share-view').addEventListener('click', () => share('view'));
    $('share-edit')?.addEventListener('click', () => share('edit'));
    $('download').addEventListener('click', async () => {
        const blob = await pngBlob(model.size, model.pixels);
        if (!blob) { status('PNG tidak dapat dibuat. Coba lagi.'); return; }
        const link = document.createElement('a'); link.href = URL.createObjectURL(blob);
        link.download = `${name.value.replace(/[^a-zA-Z0-9_-]/g, '_') || 'pixel-art'}.png`;
        link.click(); setTimeout(() => URL.revokeObjectURL(link.href), 1000); status('PNG berhasil diunduh.');
    });
    canvas.addEventListener('keydown', event => {
        if (['ArrowLeft','ArrowRight','ArrowUp','ArrowDown',' '].includes(event.key)) event.preventDefault();
        const step = {ArrowLeft:-1,ArrowRight:1,ArrowUp:-model.size,ArrowDown:model.size}[event.key];
        if (step) { keyboard = Math.max(0, Math.min(model.pixels.length - 1, keyboard + step)); $('coordinates').textContent = `X ${keyboard % model.size + 1} / Y ${Math.floor(keyboard / model.size) + 1}`; }
        if (event.key === ' ' && config.editable) { model.begin(); if (tool === 'fill') model.fill(keyboard, color); else model.paint(keyboard, tool === 'eraser' ? null : color); changed(model.end()); }
    });
    document.addEventListener('keydown', event => {
        if (event.target.closest('input,textarea,select') || !config.editable) return;
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') { event.preventDefault(); event.shiftKey ? redo() : undo(); }
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); save(); }
        if (!event.ctrlKey && !event.metaKey && {p:'pencil',e:'eraser',f:'fill'}[event.key]) chooseTool({p:'pencil',e:'eraser',f:'fill'}[event.key]);
    });
    setInterval(() => { if (config.saved && config.editable) sync.save(name.value); }, 1000);
    setInterval(() => sync.poll(), 3000);
    window.addEventListener('beforeunload', event => {
        if (sync.dirty || nameDirty || (unsaved && !config.saved)) { event.preventDefault(); event.returnValue = ''; }
    });
    refresh();
}
