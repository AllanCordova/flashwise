/**
 * Achievement Service
 * Faz requisições AJAX para carregar conquistas do usuário
 */

class AchievementService {
    constructor() {
        this.apiUrl = '/achievements';
    }

    /**
     * Carrega todas as conquistas do usuário atual
     * @returns {Promise<Object>} Promise com os dados das conquistas
     */
    async loadAchievements() {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Erro ao carregar conquistas');
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erro ao carregar conquistas:', error);
            return {
                success: false,
                error: error.message,
                achievements: []
            };
        }
    }

    /**
     * Renderiza as conquistas no container especificado
     * @param {HTMLElement} container Elemento onde as conquistas serão renderizadas
     */
    async renderAchievements(container) {
        const data = await this.loadAchievements();

        if (!data.success) {
            container.innerHTML = `
                <div class="achievements-error">
                    <i class="bi bi-exclamation-triangle"></i>
                    <p>Erro ao carregar conquistas: ${data.error || 'Erro desconhecido'}</p>
                </div>
            `;
            return;
        }

        if (!data.achievements || data.achievements.length === 0) {
            container.innerHTML = `
                <div class="achievements-empty">
                    <i class="bi bi-trophy"></i>
                    <h3>Nenhuma conquista ainda</h3>
                    <p>Continue estudando para desbloquear suas primeiras conquistas!</p>
                </div>
            `;
            return;
        }

        let html = `
            <div class="achievements-stats">
                <div class="stats-item">
                    <i class="bi bi-trophy-fill"></i>
                    <span>Total: <strong>${data.achievements.length}</strong> conquista${data.achievements.length !== 1 ? 's' : ''}</span>
                </div>
            </div>
            <div class="achievements-grid">
        `;
        
        data.achievements.forEach(achievement => {
            html += `
                <div class="achievement-card">
                    <img src="${achievement.file_path}" 
                         alt="${this.escapeHtml(achievement.title)}" 
                         class="achievement-image"
                         onerror="this.src='/assets/images/defaults/avatar.png'">
                    <div class="card-body">
                        <h5 class="card-title">${this.escapeHtml(achievement.title)}</h5>
                        <small class="text-muted">${this.formatDate(achievement.uploaded_at)}</small>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    /**
     * Escapa HTML para prevenir XSS
     * @param {string} text Texto a ser escapado
     * @returns {string} Texto escapado
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Formata data para exibição
     * @param {string} dateString String de data
     * @returns {string} Data formatada
     */
    formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
}

// Inicializa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    const achievementsContainer = document.getElementById('achievements-list');
    
    if (achievementsContainer) {
        const service = new AchievementService();
        service.renderAchievements(achievementsContainer);
    }
});

