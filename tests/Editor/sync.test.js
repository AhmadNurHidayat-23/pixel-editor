import test from 'node:test';
import assert from 'node:assert/strict';
import {ArtworkSync} from '../../resources/js/sync.js';
import {PixelDocument} from '../../resources/js/pixels.js';
test('remote pixels merge while unsaved local pixels remain visible', () => {
    const doc = new PixelDocument(16);
    const sync = new ArtworkSync({saved:true}, doc, '', () => {}, () => {}, () => false);
    sync.queue([{index:0, color:'#111111'}]);
    const pixels = Array(256).fill(null); pixels[1] = '#ffffff';
    sync.merge({pixels, version:2});
    assert.equal(doc.pixels[0], '#111111'); assert.equal(doc.pixels[1], '#ffffff');
    sync.merge({pixels:Array(256).fill(null), version:1}); assert.equal(doc.pixels[1], '#ffffff');
});
test('failed save restores sent changes without overwriting newer local edits', async () => {
    const doc = new PixelDocument(16);
    const sync = new ArtworkSync({saved:true,editable:true,writeUrl:'/x'}, doc, '', () => {}, () => {}, () => false);
    sync.queue([{index:0,color:'#111111'}]);
    sync.request = async () => { sync.queue([{index:0,color:'#ffffff'}]); throw new Error('offline'); };
    assert.equal(await sync.save('Demo'), false); assert.equal(sync.pending.get(0), '#ffffff'); assert.equal(sync.busy, false);
});
test('in-flight save preserves local edits made while awaiting server', async () => {
    const doc = new PixelDocument(16);
    const sync = new ArtworkSync({saved:true,editable:true,writeUrl:'/x'}, doc, '', () => {}, () => {}, () => false);
    sync.queue([{index:0,color:'#111111'}]);
    sync.request = async () => { sync.queue([{index:1,color:'#ffffff'}]); return {pixels:Array(256).fill('#111111'),version:2}; };
    assert.equal(await sync.save('Demo'), true); assert.equal(doc.pixels[1], '#ffffff'); assert.equal(sync.pending.size, 1);
});
test('remote poll preserves an active unfinished stroke', () => {
    const doc = new PixelDocument(16);
    doc.begin(); doc.paint(0, '#214db3');
    const sync = new ArtworkSync({saved:true}, doc, '', () => {}, () => {}, () => true);
    const remote = Array(256).fill(null); remote[1] = '#ffffff';
    sync.merge({pixels:remote, version:2});
    assert.equal(doc.pixels[0], '#214db3'); assert.equal(doc.pixels[1], '#ffffff');
    assert.deepEqual(doc.end(), [{index:0,color:'#214db3'}]);
});
