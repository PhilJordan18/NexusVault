interface Session {
    id: string;
    ip_address: string;
    user_agent: string;
    last_activity: string;
    is_current: boolean;
    device_name: string;
}

class SessionManager {
    private container: HTMLElement | null = null;

    constructor() {
        this.init();
    }

    private init(): void {
        this.bindEvents();
    }

    private bindEvents(): void {
        // Revoke single session
        document.querySelectorAll('.revoke-session-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const sessionId = (btn as HTMLElement).dataset.sessionId;

                if (sessionId) {
                    await this.revokeSession(sessionId, btn as HTMLButtonElement);
                }
            });
        });

        // Logout from all other devices
        const logoutAllBtn = document.getElementById('logout-all-sessions-btn');

        if (logoutAllBtn) {
            logoutAllBtn.addEventListener('click', async (e) => {
                e.preventDefault();

                if (confirm('Are you sure you want to log out from all other devices?')) {
                    await this.logoutFromAllOtherDevices(logoutAllBtn as HTMLButtonElement);
                }
            });
        }
    }

    private async revokeSession(sessionId: string, button: HTMLButtonElement): Promise<void> {
        const originalText = button.innerHTML;
        button.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-2"></i> Logging out...`;
        button.disabled = true;

        try {
            const response = await fetch(`/settings/sessions/${sessionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const card = button.closest('.session-card');

                if (card) {
                    card.classList.add('opacity-0', 'scale-95');
                    setTimeout(() => card.remove(), 200);
                }

                this.showToast('Session revoked successfully', 'success');
            } else {
                throw new Error('Failed to revoke session');
            }
        } catch (error) {
            console.error('Error revoking session:', error);
            this.showToast('Failed to revoke session', 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }

    private async logoutFromAllOtherDevices(button: HTMLButtonElement): Promise<void> {
        const originalText = button.innerHTML;
        button.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-2"></i> Logging out...`;
        button.disabled = true;

        try {
            const response = await fetch('/settings/sessions/logout-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                document.querySelectorAll('.session-card:not(.current-session)').forEach(card => {
                    card.classList.add('opacity-0', 'scale-95');
                    setTimeout(() => card.remove(), 200);
                });
                this.showToast('Logged out from all other devices', 'success');
            } else {
                throw new Error('Failed to logout from all devices');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showToast('Failed to logout from all devices', 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }

    private getCsrfToken(): string {
        const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;

        return meta?.content || '';
    }

    private showToast(message: string, type: 'success' | 'error' = 'success'): void {
        const toastContainer = document.getElementById('toast-container') || this.createToastContainer();

        const toast = document.createElement('div');
        toast.className = `flex items-center gap-3 px-5 py-3 rounded-2xl shadow-xl text-sm border ${
            type === 'success'
                ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400'
                : 'bg-red-500/10 border-red-500/30 text-red-400'
        }`;

        toast.innerHTML = `
            <i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;

        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    private createToastContainer(): HTMLElement {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-6 right-6 z-[100] space-y-2';
        document.body.appendChild(container);

        return container;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new SessionManager();
});
