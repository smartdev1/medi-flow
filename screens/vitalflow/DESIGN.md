---
name: VitalFlow
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#434655'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#737686'
  outline-variant: '#c3c6d7'
  surface-tint: '#0053db'
  primary: '#004ac6'
  on-primary: '#ffffff'
  primary-container: '#2563eb'
  on-primary-container: '#eeefff'
  inverse-primary: '#b4c5ff'
  secondary: '#006c49'
  on-secondary: '#ffffff'
  secondary-container: '#6cf8bb'
  on-secondary-container: '#00714d'
  tertiary: '#525556'
  on-tertiary: '#ffffff'
  tertiary-container: '#6b6d6e'
  on-tertiary-container: '#eff0f1'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#6ffbbe'
  secondary-fixed-dim: '#4edea3'
  on-secondary-fixed: '#002113'
  on-secondary-fixed-variant: '#005236'
  tertiary-fixed: '#e1e3e4'
  tertiary-fixed-dim: '#c5c7c8'
  on-tertiary-fixed: '#191c1d'
  on-tertiary-fixed-variant: '#454748'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
  emergency-red: '#EF4444'
  alert-orange: '#F59E0B'
  surface-white: '#FFFFFF'
  border-subtle: '#E2E8F0'
typography:
  display-lg:
    fontFamily: Manrope
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Manrope
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
  headline-md:
    fontFamily: Manrope
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 30px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  label-caps:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.05em
  data-mono:
    fontFamily: JetBrains Mono
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  container-padding: 24px
  gutter: 16px
  section-gap: 32px
  radius-sm: 4px
  radius-md: 8px
  radius-lg: 16px
---

## Brand & Style

The design system is engineered for high-stakes clinical environments where clarity, speed of cognition, and professional trust are paramount. The brand personality is "Clinical Precision," characterized by a calm, organized, and reliable atmosphere. It targets healthcare professionals—from administrative staff to specialized surgeons—who require a tool that minimizes cognitive load during intensive shifts.

The chosen style is **Minimalist / Corporate Modern**. It utilizes heavy whitespace to separate dense medical data, a luminous light-mode default to maintain an "operating room" cleanliness, and subtle depth to organize complex information hierarchies without visual clutter. The interface feels intentional and hygienic, avoiding unnecessary decoration in favor of functional elegance.

## Colors

The palette is rooted in "Medical Blue" (#2563EB) to evoke trust and authority, paired with "Health Green" (#10B981) for positive outcomes and system "Go" states. 

- **Primary:** Used for main actions, active navigation states, and primary branding elements.
- **Secondary:** Reserved for health-positive indicators, successful validations, and wellness-related modules.
- **Neutral:** A range of slate grays used for typography and UI scaffolding to ensure high legibility.
- **Functional Accents:** Emergency Red and Alert Orange are used sparingly and exclusively for critical patient states or system warnings to prevent "alert fatigue."
- **Surfaces:** The application uses a "White on Very Light Gray" approach to create distinct sectional layering without relying on heavy borders.

## Typography

The typography system prioritizes rapid scanning of patient data and clear hierarchical differentiation.

- **Headlines (Manrope):** A modern, semi-geometric sans-serif that provides a professional yet approachable feel for dashboard titles and section headers.
- **Body (Inter):** Chosen for its exceptional legibility at small sizes and high "x-height," perfect for medical records and long-form notes.
- **Labels & Data (JetBrains Mono):** Used specifically for Patient IDs, Visit IDs, and timestamps. The monospaced nature ensures that numeric data remains aligned in tables and timelines, aiding in quick comparisons.

Large display sizes are scaled down by 20% on mobile devices to maintain readability without excessive scrolling.

## Layout & Spacing

This design system employs a **Fluid Grid** model with fixed maximum widths for content containers (1440px) to prevent line lengths from becoming unreadable on ultra-wide monitors. 

- **Grid:** A 12-column system is used for dashboards. Statistics cards typically span 3 columns on desktop, 6 on tablet, and 12 on mobile.
- **Rhythm:** An 8px linear scale governs all padding and margins, ensuring a consistent visual beat.
- **Reflow:** On mobile, the persistent sidebar collapses into a bottom navigation bar or a "hamburger" drawer, while data tables transition into a "card-stack" format to preserve data integrity on narrow screens.

## Elevation & Depth

Visual hierarchy is achieved through **Tonal Layering** and **Ambient Shadows**. 

- **Background:** The lowest layer is the Tertiary color (#F9FAFB), providing a soft canvas.
- **Cards & Containers:** Pure white (#FFFFFF) surfaces sit atop the background with a very soft, diffused shadow (0px 4px 20px rgba(0, 0, 0, 0.03)). This creates a sense of "physicality" where patient files appear to float slightly above the desk.
- **Interactive Elements:** Buttons and active inputs use a slightly more pronounced shadow on hover to indicate interactability. 
- **Modals:** These utilize a backdrop blur (10px) and a higher elevation shadow to pull the user's focus away from the background data during critical entries (e.g., prescribing medication).

## Shapes

The shape language is **Rounded**, moving away from the cold, sharp corners of legacy medical software. 

Standard components (inputs, buttons) use a 0.5rem (8px) radius. Larger layout containers and statistics cards use a 1rem (16px) radius. This "soft-tech" aesthetic reduces the perceived complexity of the software and makes the clinical environment feel more modern and user-friendly. High-emphasis badges (like status indicators) may use full pill-shaping (rounded-full) for immediate recognition.

## Components

### Statistics Cards
Large, white containers with a 16px radius. They feature a top-aligned "Label-Caps" title, a "Display-LG" value, and a small Sparkline chart or percentage badge in the bottom corner.

### Modern Tables
Clean, borderless rows separated by subtle 1px horizontal lines (#E2E8F0). Headers are in "Label-Caps" with a light gray background. Row hover states use the Tertiary color to highlight the currently viewed patient.

### Status Badges
Pill-shaped with a light background tint and dark text of the same hue (e.g., "In Consultation" uses a light blue fill with a dark blue text).

### Vertical Timeline
A 2px solid track in #E2E8F0. Each node is a 12px circle colored by service (Blue for Accueil, Green for Pharmacie). Active/Current steps are pulsed with a subtle glow. Content to the right of the node includes "Data-Mono" timestamps.

### Inputs & Fields
Outlined style using #E2E8F0 borders. Focused states use a 2px Primary Blue border with a soft glow. Validation errors are indicated by Emergency Red text and icons.

### Vertical Navigation (Sidebar)
A slim, dark-neutral or white sidebar. Icons are Lucide-based, 24px, with a Primary Blue indicator bar for the active state.