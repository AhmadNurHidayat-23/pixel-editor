import test from 'node:test';
import assert from 'node:assert/strict';
import {PixelDocument} from '../../resources/js/pixels.js';
test('drag interpolates pixels without gaps and one stroke undoes atomically', () => {
    const doc = new PixelDocument(16);
    doc.begin(); doc.line(0, 15, '#214db3');
    assert.equal(doc.end().length, 16);
    assert.deepEqual(doc.pixels.slice(0, 16), Array(16).fill('#214db3'));
    doc.undo(); assert.ok(doc.pixels.every(c => c === null));
    doc.redo(); assert.equal(doc.pixels[15], '#214db3');
});
test('fill respects connected regions and eraser is undoable', () => {
    const doc = new PixelDocument(16);
    doc.begin(); doc.line(1, 241, '#111111'); doc.end();
    doc.begin(); doc.fill(0, '#214db3'); assert.equal(doc.end().length, 16);
    assert.equal(doc.pixels[2], null);
    doc.begin(); doc.paint(0, null); doc.end(); assert.equal(doc.pixels[0], null);
    doc.undo(); assert.equal(doc.pixels[0], '#214db3');
});
test('clear can be undone, branch clears redo, and blank stroke has no history', () => {
    const doc = new PixelDocument(32);
    doc.begin(); doc.paint(1023, '#ffffff'); doc.end();
    doc.clear(); assert.equal(doc.pixels[1023], null);
    doc.undo(); assert.equal(doc.pixels[1023], '#ffffff');
    doc.begin(); doc.paint(0, '#111111'); doc.end(); assert.equal(doc.redoStack.length, 0);
    doc.begin(); assert.deepEqual(doc.end(), []);
});
test('repeated stroke pixel keeps original color for undo', () => {
    const doc = new PixelDocument(16);
    doc.begin(); doc.paint(0, '#111111'); doc.paint(0, '#ffffff'); doc.end();
    doc.undo(); assert.equal(doc.pixels[0], null);
});
