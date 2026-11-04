export class ArtworkSync {
    constructor(config, document, storeUrl, status, refresh, drawing) {
        Object.assign(this, {config, document, storeUrl, status, refresh, drawing});
        this.pending = new Map();
        this.busy = false;
        this.polling = false;
        this.version = config.version ?? 0;
    }
    queue(changes) { changes.forEach(c => this.pending.set(c.index, c.color)); }
    get dirty() { return this.pending.size > 0 || this.busy; }
    async request(url, method = 'GET', data) {
        const response = await fetch(url, {
            method, credentials: 'same-origin', headers: {
                'Accept': 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }, body: data ? JSON.stringify(data) : undefined,
            signal: AbortSignal.timeout(15000), cache: 'no-store',
        });
        if (!response.ok) {
            const body = await response.json().catch(() => ({}));
            const message = Object.values(body.errors ?? {}).flat()[0];
            throw new Error(message ?? (response.status === 419 ? 'Sesi berakhir. Muat ulang setelah menyalin tautan atau mengunduh PNG.' : 'Gagal menyimpan. Periksa koneksi dan hak akses, lalu coba lagi.'));
        }
        return response.json();
    }
    merge(state) {
        if (state.version < this.version) return;
        this.version = state.version;
        this.document.pixels = [...state.pixels];
        this.pending.forEach((color, index) => { this.document.pixels[index] = color; });
        this.document.stroke.forEach(c => { this.document.pixels[c.index] = c.after; });
        this.refresh();
    }
    async save(name, explicit = false) {
        if (this.busy || this.polling || this.drawing()) return false;
        if (!this.config.editable) return false;
        if (!this.config.saved && !explicit) return false;
        if (this.config.saved && !this.pending.size && !explicit) return true;
        this.busy = true;
        const batch = new Map(this.pending);
        this.pending.clear();
        const creating = !this.config.saved;
        this.status('Menyimpan…');
        try {
            const data = creating ? {name, size: this.document.size, pixels: this.document.pixels} : {
                changes: [...batch].map(([index, color]) => ({index, color})), ...(explicit ? {name} : {}),
            };
            const state = await this.request(creating ? this.storeUrl : this.config.writeUrl, creating ? 'POST' : 'PATCH', data);
            if (creating) {
                Object.assign(this.config, state);
                history.replaceState(null, '', state.readUrl.replace('/state', '/edit'));
            }
            this.merge(state);
            this.status(this.pending.size ? 'Perubahan menunggu disimpan' : 'Tersimpan · sinkronisasi aktif');
            return true;
        } catch (error) {
            batch.forEach((color, index) => { if (!this.pending.has(index)) this.pending.set(index, color); });
            this.status(error.message);
            return false;
        } finally { this.busy = false; }
    }
    async poll() {
        if (!this.config.saved || this.busy || this.polling || this.drawing() || document.hidden) return;
        this.polling = true;
        try { this.merge(await this.request(this.config.readUrl)); }
        catch { this.status('Sinkronisasi tertunda. Perubahan lokal tetap dipertahankan.'); }
        finally { this.polling = false; }
    }
}
export async function copyLink(url, status) {
    try {
        if (!navigator.clipboard?.writeText) throw new Error();
        await navigator.clipboard.writeText(url);
        status('Tautan berhasil disalin.');
    } catch { window.prompt('Salin tautan berikut:', url); }
}
