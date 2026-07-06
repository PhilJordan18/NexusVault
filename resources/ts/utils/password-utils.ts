type PasswordGeneratorOptions = {
    length: number;
    upper: boolean;
    lower: boolean;
    numbers: boolean;
    symbols: boolean;
    avoidAmbiguous: boolean;
};

export type PasswordEntropy = {
    entropy: number;
    strength: string;
    label: string;
};

const STORAGE_KEY = 'nexusvault:password-generator:v1';
const MIN_LENGTH = 8;
const MAX_LENGTH = 64;
const DEFAULT_OPTIONS: PasswordGeneratorOptions = {
    length: 18,
    upper: true,
    lower: true,
    numbers: true,
    symbols: true,
    avoidAmbiguous: false,
};

const CHARACTER_GROUPS = {
    upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    lower: 'abcdefghijklmnopqrstuvwxyz',
    numbers: '0123456789',
    symbols: '!@#$%^&*()-_=+[]{}|;:,.<>?',
};

const AMBIGUOUS_CHARACTERS = new Set('0O1Il|`\'"'.split(''));

function t(key: string): string {
    const translations = (window as any).nexusVaultTranslations as Record<string, string> | undefined;

    return translations?.[key] ?? key;
}

function clampLength(length: number, minimumLength = MIN_LENGTH): number {
    return Math.min(Math.max(length, minimumLength, MIN_LENGTH), MAX_LENGTH);
}

function storageAvailable(): boolean {
    try {
        return typeof window !== 'undefined' && Boolean(window.localStorage);
    } catch {
        return false;
    }
}

function selectedGroups(options: PasswordGeneratorOptions): string[] {
    const groups = [
        options.upper ? CHARACTER_GROUPS.upper : '',
        options.lower ? CHARACTER_GROUPS.lower : '',
        options.numbers ? CHARACTER_GROUPS.numbers : '',
        options.symbols ? CHARACTER_GROUPS.symbols : '',
    ].filter(Boolean);

    return groups.map(group => {
        if (!options.avoidAmbiguous) {
            return group;
        }

        const filtered = [...group].filter(character => !AMBIGUOUS_CHARACTERS.has(character)).join('');

        return filtered || group;
    });
}

function normalizeOptions(options: Partial<PasswordGeneratorOptions> = {}, minimumLength = MIN_LENGTH): PasswordGeneratorOptions {
    const merged = { ...DEFAULT_OPTIONS, ...loadPasswordGeneratorOptions(), ...options };

    if (!merged.upper && !merged.lower && !merged.numbers && !merged.symbols) {
        merged.lower = true;
    }

    return {
        ...merged,
        length: clampLength(Number(merged.length) || DEFAULT_OPTIONS.length, minimumLength),
        upper: Boolean(merged.upper),
        lower: Boolean(merged.lower),
        numbers: Boolean(merged.numbers),
        symbols: Boolean(merged.symbols),
        avoidAmbiguous: Boolean(merged.avoidAmbiguous),
    };
}

export function loadPasswordGeneratorOptions(): PasswordGeneratorOptions {
    if (!storageAvailable()) {
        return { ...DEFAULT_OPTIONS };
    }

    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        if (!stored) {
            return { ...DEFAULT_OPTIONS };
        }

        return { ...DEFAULT_OPTIONS, ...JSON.parse(stored) } as PasswordGeneratorOptions;
    } catch {
        return { ...DEFAULT_OPTIONS };
    }
}

function savePasswordGeneratorOptions(options: PasswordGeneratorOptions): void {
    if (!storageAvailable()) {
        return;
    }

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(options));
}

function randomInt(maxExclusive: number): number {
    if (maxExclusive <= 0) {
        throw new Error('Cannot generate a random number without characters.');
    }

    const limit = 256 - (256 % maxExclusive);
    const random = new Uint8Array(1);

    do {
        window.crypto.getRandomValues(random);
    } while (random[0] >= limit);

    return random[0] % maxExclusive;
}

function randomCharacter(characters: string): string {
    return characters[randomInt(characters.length)];
}

function shuffleCharacters(characters: string[]): string[] {
    const shuffled = [...characters];

    for (let index = shuffled.length - 1; index > 0; index -= 1) {
        const swapIndex = randomInt(index + 1);
        [shuffled[index], shuffled[swapIndex]] = [shuffled[swapIndex], shuffled[index]];
    }

    return shuffled;
}

export function generatePassword(options: Partial<PasswordGeneratorOptions> = {}, minimumLength = MIN_LENGTH): string {
    const normalizedOptions = normalizeOptions(options, minimumLength);
    const groups = selectedGroups(normalizedOptions);
    const allCharacters = groups.join('');
    const passwordCharacters = groups.map(randomCharacter);

    while (passwordCharacters.length < normalizedOptions.length) {
        passwordCharacters.push(randomCharacter(allCharacters));
    }

    return shuffleCharacters(passwordCharacters).join('');
}

export function generatePasswordForButton(button: HTMLElement): string {
    const minimumLength = Number(button.dataset.passwordMinLength ?? MIN_LENGTH);

    return generatePassword({}, minimumLength);
}

export async function calculateEntropy(password: string): Promise<PasswordEntropy> {
    return calculatePasswordEntropy(password);
}

export function calculatePasswordEntropy(password: string): PasswordEntropy {
    const length = password.length;

    if (length === 0) {
        return { entropy: 0, strength: 'very_weak', label: 'Very weak' };
    }

    let charsetSize = 0;

    if (/[a-z]/.test(password)) {
        charsetSize += 26;
    }

    if (/[A-Z]/.test(password)) {
        charsetSize += 26;
    }

    if (/[0-9]/.test(password)) {
        charsetSize += 10;
    }

    if (/[^a-zA-Z0-9]/.test(password)) {
        charsetSize += CHARACTER_GROUPS.symbols.length;
    }

    const entropy = charsetSize > 0 ? length * Math.log2(charsetSize) : 0;

    return {
        entropy: Math.round(entropy * 100) / 100,
        strength: strengthForEntropy(entropy),
        label: labelForEntropy(entropy),
    };
}

function strengthForEntropy(entropy: number): string {
    if (entropy < 40) {
        return 'very_weak';
    }

    if (entropy < 60) {
        return 'weak';
    }

    if (entropy < 80) {
        return 'medium';
    }

    if (entropy < 100) {
        return 'strong';
    }

    return 'very_strong';
}

function labelForEntropy(entropy: number): string {
    if (entropy < 40) {
        return 'Very weak';
    }

    if (entropy < 60) {
        return 'Weak';
    }

    if (entropy < 80) {
        return 'Medium';
    }

    if (entropy < 100) {
        return 'Strong';
    }

    return 'Very strong';
}

export function renderPasswordStrength(
    strengthContainer: HTMLElement,
    strengthBar: HTMLElement,
    strengthText: HTMLElement,
    result: PasswordEntropy
): void {
    const percentage = Math.min((result.entropy / 100) * 100, 100);
    strengthBar.style.width = `${percentage}%`;
    strengthText.textContent = t(result.label);

    if (result.strength === 'very_weak' || result.strength === 'weak') {
        strengthBar.style.backgroundColor = '#ff4444';
    } else if (result.strength === 'medium') {
        strengthBar.style.backgroundColor = '#ffaa00';
    } else {
        strengthBar.style.backgroundColor = '#00ff9d';
    }

    strengthContainer.classList.remove('hidden');
}

function createIcon(className: string): HTMLElement {
    const icon = document.createElement('i');
    icon.className = className;

    return icon;
}

function createCheckboxControl(
    label: string,
    checked: boolean,
    onChange: (checked: boolean) => void
): HTMLLabelElement {
    const wrapper = document.createElement('label');
    wrapper.className = 'flex items-center justify-between gap-3 rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)] px-3 py-2 text-xs';

    const text = document.createElement('span');
    text.textContent = label;

    const input = document.createElement('input');
    input.type = 'checkbox';
    input.checked = checked;
    input.className = 'h-4 w-4 accent-emerald-500';
    input.addEventListener('change', () => onChange(input.checked));

    wrapper.append(text, input);

    return wrapper;
}

function bindLengthInputs(
    panel: HTMLElement,
    options: PasswordGeneratorOptions,
    minimumLength: number,
    onChange: (length: number) => void
): void {
    const range = panel.querySelector<HTMLInputElement>('[data-password-generator-length-range]');
    const number = panel.querySelector<HTMLInputElement>('[data-password-generator-length-number]');

    if (!range || !number) {
        return;
    }

    range.min = minimumLength.toString();
    range.max = MAX_LENGTH.toString();
    range.value = options.length.toString();
    number.min = minimumLength.toString();
    number.max = MAX_LENGTH.toString();
    number.value = options.length.toString();

    const syncLength = (value: string): void => {
        const length = clampLength(Number(value), minimumLength);
        range.value = length.toString();
        number.value = length.toString();
        onChange(length);
    };

    range.addEventListener('input', () => syncLength(range.value));
    number.addEventListener('input', () => syncLength(number.value));
}

function createPasswordGeneratorControls(button: HTMLElement): HTMLElement {
    const minimumLength = Number(button.dataset.passwordMinLength ?? MIN_LENGTH);
    let options = normalizeOptions({}, minimumLength);

    const wrapper = document.createElement('div');
    wrapper.dataset.passwordGeneratorControlsFor = button.id;
    wrapper.className = 'mt-3';

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'flex w-full items-center justify-center gap-2 text-xs font-medium text-[var(--text-secondary)] transition hover:text-emerald-400';
    toggle.append(createIcon('fa-solid fa-sliders'));
    const toggleText = document.createElement('span');
    toggleText.textContent = t('Customize generator');
    toggle.appendChild(toggleText);

    const panel = document.createElement('div');
    panel.className = 'mt-3 hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-input)]/40 p-4';

    const lengthRow = document.createElement('div');
    lengthRow.className = 'mb-4';

    const lengthHeader = document.createElement('div');
    lengthHeader.className = 'mb-2 flex items-center justify-between gap-3 text-xs';

    const lengthLabel = document.createElement('span');
    lengthLabel.className = 'text-[var(--text-secondary)]';
    lengthLabel.textContent = t('Length');

    const lengthNumber = document.createElement('input');
    lengthNumber.type = 'number';
    lengthNumber.dataset.passwordGeneratorLengthNumber = 'true';
    lengthNumber.className = 'w-16 rounded-xl border border-[var(--border-color)] bg-[var(--bg-input)] px-2 py-1 text-center text-xs outline-none focus:border-emerald-500';

    const lengthRange = document.createElement('input');
    lengthRange.type = 'range';
    lengthRange.dataset.passwordGeneratorLengthRange = 'true';
    lengthRange.className = 'w-full accent-emerald-500';

    lengthHeader.append(lengthLabel, lengthNumber);
    lengthRow.append(lengthHeader, lengthRange);

    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-1 gap-2 sm:grid-cols-2';

    const setOption = (key: keyof PasswordGeneratorOptions, value: boolean | number): void => {
        options = normalizeOptions({ ...options, [key]: value }, minimumLength);
        savePasswordGeneratorOptions(options);
    };

    grid.append(
        createCheckboxControl(t('Uppercase'), options.upper, value => setOption('upper', value)),
        createCheckboxControl(t('Lowercase'), options.lower, value => setOption('lower', value)),
        createCheckboxControl(t('Numbers'), options.numbers, value => setOption('numbers', value)),
        createCheckboxControl(t('Symbols'), options.symbols, value => setOption('symbols', value)),
        createCheckboxControl(t('Avoid ambiguous'), options.avoidAmbiguous, value => setOption('avoidAmbiguous', value))
    );

    panel.append(lengthRow, grid);
    bindLengthInputs(panel, options, minimumLength, length => setOption('length', length));
    toggle.addEventListener('click', () => panel.classList.toggle('hidden'));
    wrapper.append(toggle, panel);

    return wrapper;
}

export function bindPasswordGeneratorCustomization(root: ParentNode = document): void {
    root.querySelectorAll<HTMLElement>('[data-password-generate]').forEach(button => {
        if (button.dataset.passwordGeneratorCustomizationBound === 'true') {
            return;
        }

        button.dataset.passwordGeneratorCustomizationBound = 'true';
        const controls = createPasswordGeneratorControls(button);
        button.insertAdjacentElement('afterend', controls);
    });
}

/**
 * Initialise la barre de force et le bouton de génération pour un champ password donné.
 */
export function bindPasswordStrength(
    passwordInputId: string,
    toggleBtnId: string | null,
    strengthContainerId: string,
    strengthBarId: string,
    strengthTextId: string,
    generateBtnId: string | null,
    confirmationInputId?: string
): void {
    const passwordInput = document.getElementById(passwordInputId) as HTMLInputElement | null;

    if (!passwordInput) {
        return;
    }

    const strengthContainer = document.getElementById(strengthContainerId);
    const strengthBar = document.getElementById(strengthBarId) as HTMLElement | null;
    const strengthText = document.getElementById(strengthTextId) as HTMLElement | null;
    let entropyTimer: number | null = null;
    let entropyRequestId = 0;

    if (toggleBtnId) {
        const toggleBtn = document.getElementById(toggleBtnId);

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const icon = toggleBtn.querySelector('i') as HTMLElement | null;
                const nextType = passwordInput.type === 'password' ? 'text' : 'password';

                passwordInput.type = nextType;

                if (nextType === 'text') {
                    icon?.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    icon?.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        }
    }

    passwordInput.addEventListener('input', () => {
        if (!strengthContainer || !strengthBar || !strengthText) {
            return;
        }

        const requestId = entropyRequestId + 1;
        entropyRequestId = requestId;

        if (entropyTimer !== null) {
            window.clearTimeout(entropyTimer);
        }

        if (passwordInput.dataset.passwordStrengthDisabled === 'true') {
            strengthContainer.classList.add('hidden');

            return;
        }

        if (passwordInput.value.length < 4) {
            renderPasswordStrength(strengthContainer, strengthBar, strengthText, {
                entropy: 0,
                strength: 'very_weak',
                label: 'Very weak',
            });

            return;
        }

        entropyTimer = window.setTimeout(() => {
            if (requestId !== entropyRequestId) {
                return;
            }

            renderPasswordStrength(strengthContainer, strengthBar, strengthText, calculatePasswordEntropy(passwordInput.value));
        }, 150);
    });

    if (generateBtnId) {
        const generateBtn = document.getElementById(generateBtnId);

        if (generateBtn) {
            generateBtn.dataset.passwordGenerate = 'true';
            generateBtn.dataset.passwordMinLength ??= MIN_LENGTH.toString();
            generateBtn.addEventListener('click', () => {
                if (passwordInput.dataset.passwordStrengthDisabled === 'true') {
                    return;
                }

                const newPassword = generatePasswordForButton(generateBtn);
                passwordInput.value = newPassword;
                passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
                passwordInput.setCustomValidity('');

                if (confirmationInputId) {
                    const confirmInput = document.getElementById(confirmationInputId) as HTMLInputElement | null;

                    if (confirmInput) {
                        confirmInput.value = newPassword;
                        confirmInput.setCustomValidity('');
                    }
                }
            });
        }
    }

    bindPasswordGeneratorCustomization();
}
