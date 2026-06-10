window.MOS = window.MOS || {};
window.MOS.customer = window.MOS.customer || {};

window.MOS.customer.createPlanModule = function createPlanModule(context) {
    const {
        plans,
        state,
        categories,
        formatYen,
        findPlan,
        showScreen,
        renderMenu
    } = context;

    function openPlanModal(planId) {
        const plan = findPlan(planId);

        if (!plan) {
            return;
        }

        document.getElementById('modalPlanName').textContent = plan.name;
        document.getElementById('modalPlanPrice').textContent = formatYen(plan.price);
        document.getElementById('modalPlanDetails').innerHTML = plan.details
            .map(detail => `<li>${detail}</li>`)
            .join('');

        document.getElementById('planConfirmButton').dataset.planId = plan.id;
        document.getElementById('planModal').classList.add('show');
    }

    function closePlanModal() {
        document.getElementById('planModal').classList.remove('show');
    }

    function bindPlanEvents() {
        document.querySelectorAll('.plan-banner').forEach(button => {
            button.addEventListener('click', () => {
                openPlanModal(button.dataset.planId);
            });
        });

        document.getElementById('singleOrderButton').addEventListener('click', () => {
            openPlanModal('single');
        });

        document.getElementById('closePlanModalButton').addEventListener('click', () => {
            closePlanModal();
        });

        document.getElementById('planConfirmButton').addEventListener('click', event => {
            const planId = event.currentTarget.dataset.planId;

            state.selectedPlanId = planId;
            closePlanModal();

            state.activeCategory = planId === 'single'
                ? categories[0]
                : categories[0];

            renderMenu();
            showScreen('menuScreen');
        });
    }

    return {
        bindPlanEvents,
        openPlanModal,
        closePlanModal
    };
};
