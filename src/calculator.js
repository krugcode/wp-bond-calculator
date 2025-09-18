import { mount } from 'svelte';
import TransferCostCalculator from './TransferCostCalculator.svelte';
import BondCostCalculator from './BondCostCalculator.svelte';

// Function to mount calculators
function mountCalculators() {
  // Mount Transfer Cost Calculators
  const transferElements = document.querySelectorAll('.bc-transfer-calculator');
  transferElements.forEach(element => {
    if (!element.hasAttribute('data-mounted')) {
      mount(TransferCostCalculator, {
        target: element
      });
      element.setAttribute('data-mounted', 'true');
    }
  });

  // Mount Bond Cost Calculators
  const bondElements = document.querySelectorAll('.bc-bond-calculator');
  bondElements.forEach(element => {
    if (!element.hasAttribute('data-mounted')) {
      mount(BondCostCalculator, {
        target: element
      });
      element.setAttribute('data-mounted', 'true');
    }
  });
}

// Mount when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mountCalculators);
} else {
  mountCalculators();
}

// Also mount when new content is dynamically added (for themes that use AJAX)
const observer = new MutationObserver((mutations) => {
  let shouldCheck = false;
  mutations.forEach((mutation) => {
    if (mutation.addedNodes.length > 0) {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          if (node.classList && (node.classList.contains('bc-transfer-calculator') || node.classList.contains('bc-bond-calculator'))) {
            shouldCheck = true;
          } else if (node.querySelector && (node.querySelector('.bc-transfer-calculator') || node.querySelector('.bc-bond-calculator'))) {
            shouldCheck = true;
          }
        }
      });
    }
  });

  if (shouldCheck) {
    setTimeout(mountCalculators, 100); // Small delay to ensure DOM is settled
  }
});

// Start observing
observer.observe(document.body, {
  childList: true,
  subtree: true
});
