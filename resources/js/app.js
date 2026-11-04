import {confirmAction} from './dialogs';
import {startEditor} from './editor';
import {render} from './pixels';
import {copyLink} from './sync';
const editor = document.getElementById('editor');
if (editor) startEditor(editor);
document.querySelectorAll('[data-preview]').forEach(canvas => {
    const {size, pixels} = JSON.parse(canvas.dataset.preview);
    render(canvas, size, pixels);
});
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', async event => {
    event.preventDefault();
    if (await confirmAction(form.dataset.confirm)) form.submit();
}));
document.querySelectorAll('[data-copy]').forEach(button => button.addEventListener('click', () => {
    copyLink(button.dataset.copy, message => { document.getElementById('copy-status').textContent = message; });
}));
