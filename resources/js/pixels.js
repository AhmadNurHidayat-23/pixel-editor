export class PixelDocument {
    constructor(size, pixels = Array(size * size).fill(null)) {
        this.size = size;
        this.pixels = [...pixels];
        this.undoStack = [];
        this.redoStack = [];
        this.stroke = new Map();
    }
    begin() { this.stroke = new Map(); }
    paint(index, color) {
        if (index < 0 || index >= this.pixels.length || this.pixels[index] === color) return;
        if (!this.stroke.has(index)) this.stroke.set(index, {index, before: this.pixels[index], after: color});
        this.stroke.get(index).after = color;
        this.pixels[index] = color;
    }
    line(from, to, color) {
        let x = from % this.size, y = Math.floor(from / this.size);
        const x2 = to % this.size, y2 = Math.floor(to / this.size);
        const dx = Math.abs(x2 - x), dy = -Math.abs(y2 - y);
        const sx = x < x2 ? 1 : -1, sy = y < y2 ? 1 : -1;
        let error = dx + dy;
        while (true) {
            this.paint(y * this.size + x, color);
            if (x === x2 && y === y2) break;
            const twice = 2 * error;
            if (twice >= dy) { error += dy; x += sx; }
            if (twice <= dx) { error += dx; y += sy; }
        }
    }
    fill(index, color) {
        const target = this.pixels[index];
        if (target === color) return;
        const queue = [index];
        while (queue.length) {
            const i = queue.pop();
            if (this.pixels[i] !== target) continue;
            this.paint(i, color);
            const x = i % this.size, y = Math.floor(i / this.size);
            if (x > 0) queue.push(i - 1);
            if (x < this.size - 1) queue.push(i + 1);
            if (y > 0) queue.push(i - this.size);
            if (y < this.size - 1) queue.push(i + this.size);
        }
    }
    end() {
        const changes = [...this.stroke.values()].filter(c => c.before !== c.after);
        if (changes.length) { this.undoStack.push(changes); this.redoStack = []; }
        if (this.undoStack.length > 100) this.undoStack.shift();
        this.stroke.clear();
        return changes.map(c => ({index: c.index, color: c.after}));
    }
    undo() { return this.replay(this.undoStack, this.redoStack, 'before'); }
    redo() { return this.replay(this.redoStack, this.undoStack, 'after'); }
    replay(source, destination, direction) {
        const stroke = source.pop();
        if (!stroke) return [];
        destination.push(stroke);
        return stroke.map(c => { this.pixels[c.index] = c[direction]; return {index: c.index, color: c[direction]}; });
    }
    clear() { this.begin(); this.pixels.forEach((_, i) => this.paint(i, null)); return this.end(); }
}
export function render(canvas, size, pixels, grid = false) {
    const ctx = canvas.getContext('2d');
    const cell = canvas.width / size;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    pixels.forEach((color, index) => {
        const x = index % size * cell, y = Math.floor(index / size) * cell;
        if (color) { ctx.fillStyle = color; ctx.fillRect(x, y, cell, cell); }
        else if (grid) { ctx.fillStyle = '#F7F4EC'; ctx.fillRect(x, y, cell, cell); }
    });
    if (grid) {
        ctx.beginPath(); ctx.strokeStyle = '#11111140'; ctx.lineWidth = 0.5;
        for (let i = 0; i <= size; i++) {
            const v = i * cell;
            ctx.moveTo(v, 0); ctx.lineTo(v, canvas.height);
            ctx.moveTo(0, v); ctx.lineTo(canvas.width, v);
        }
        ctx.stroke();
    }
}
export function pngBlob(size, pixels) {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 512;
    render(canvas, size, pixels);
    return new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
}
