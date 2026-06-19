/**
 * Format No. VA: prefix + NIS (total 16 digit).
 * Contoh: 797783 + 123 => 7977830000000123
 */
(function (window) {
    const DEFAULT_PREFIX = '797783';
    const TOTAL_LENGTH = 16;

    window.formatNoVA = function (nis, prefix) {
        const vaPrefix = String(prefix ?? window.APP_VA_PREFIX ?? DEFAULT_PREFIX).replace(/\D/g, '') || DEFAULT_PREFIX;
        const digits = String(nis ?? '').replace(/\D/g, '');
        if (!digits) {
            return '';
        }
        const padLen = Math.max(1, TOTAL_LENGTH - vaPrefix.length);
        return vaPrefix + digits.padStart(padLen, '0');
    };
})(window);
