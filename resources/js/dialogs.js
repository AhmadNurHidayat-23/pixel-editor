export function confirmAction(message) {
    const dialog = document.getElementById('confirmation');
    document.getElementById('confirm-message').textContent = message;
    return new Promise(resolve => {
        const cleanup = result => { dialog.close(); resolve(result); };
        document.getElementById('confirm-cancel').onclick = () => cleanup(false);
        document.getElementById('confirm-accept').onclick = () => cleanup(true);
        dialog.oncancel = event => { event.preventDefault(); cleanup(false); };
        dialog.showModal();
    });
}
