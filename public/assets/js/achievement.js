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
            // Usa ícone se disponível, senão usa imagem como fallback
            const iconClass = achievement.icon || 'bi-trophy';
            const colorClass = achievement.color_class || 'achievement-primary';
            const description = achievement.description || '';
            
            html += `
                <div class="achievement-card ${colorClass}">
                    <div class="achievement-icon-wrapper">
                        <i class="bi ${iconClass} achievement-icon"></i>
                    </div>
                    <div class="achievement-content">
                        <h5 class="achievement-title">${this.escapeHtml(achievement.title)}</h5>
                        ${description ? `<p class="achievement-description">${this.escapeHtml(description)}</p>` : ''}
                        <small class="achievement-date">${this.formatDate(achievement.uploaded_at)}</small>
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

