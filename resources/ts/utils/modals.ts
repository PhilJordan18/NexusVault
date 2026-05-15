export function showShareModal(serviceId: number | string) {
    const input = document.getElementById('modal-service-id') as HTMLInputElement | null;
    const modal = document.getElementById('share-modal');

    if (input && modal) {
        input.value = serviceId.toString();
        modal.classList.remove('hidden');
    }
}

export function hideShareModal() {
    const modal = document.getElementById('share-modal');

    if (modal) {
        modal.classList.add('hidden');
    }
}
