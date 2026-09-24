document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('productType');
    if (!type) return;
    const digital = document.getElementById('digitalProductFields');
    const technicalHint = document.getElementById('technicalSheetHint');
    const digitalCostHint = document.getElementById('digitalCostHint');
    const priceSectionDescription = document.getElementById('priceSectionDescription');
    const madeToOrder = document.getElementById('madeToOrderOption');
    const personalization = document.getElementById('personalizationOption');
    function syncType() {
        const isDigital = type.value === 'virtual';
        digital.hidden = !isDigital;
        technicalHint.hidden = isDigital;
        digitalCostHint.hidden = !isDigital;
        priceSectionDescription.textContent = isDigital
            ? 'Defina o preço do arquivo e acompanhe a margem sem etapas de produção física.'
            : 'O custo será recalculado pela ficha técnica quando materiais e operações forem cadastrados.';
        madeToOrder.hidden = isDigital;
        personalization.hidden = isDigital;
        if (isDigital) {
            madeToOrder.querySelector('[type="checkbox"]').checked = false;
            personalization.querySelector('[type="checkbox"]').checked = false;
        }
    }
    type.addEventListener('change', syncType);
    syncType();
});
