// @ts-nocheck

export const tooltip = () => {
  const tooltipList = Array.prototype.slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]'),
  );
  tooltipList.map(function (tooltipElement) {
    return Tooltip.getOrCreateInstance(tooltipElement);
  });
};
