export type LegalContactParagraph = {
    text: string;
    email: string;
    suffix: string;
};

export type LegalParagraph = string | LegalContactParagraph;

export type LegalSection = {
    heading: string;
    paragraphs: LegalParagraph[];
};

export type LegalPageName = 'terms' | 'privacy' | 'cookies' | 'accessibility';
