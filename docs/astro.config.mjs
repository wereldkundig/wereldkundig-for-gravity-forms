// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';
import starlightLinksValidator from 'starlight-links-validator';

// https://astro.build/config
export default defineConfig({
	// Set to the final domain once it is connected in Sevalla, e.g. 'https://docs.example.nl'.
	// Starlight uses it for canonical URLs and the sitemap.
	// site: '',
	integrations: [
		starlight({
			title: { en: 'Wereldkundig for Gravity Forms', nl: 'Wereldkundig for Gravity Forms' },
			description: 'Subscribe Gravity Forms submitters to Wereldkundig themes.',
			// English at the root, so the existing URLs keep working; Dutch under /nl/, in src/content/docs/nl/.
			defaultLocale: 'root',
			locales: {
				root: { label: 'English', lang: 'en' },
				nl: { label: 'Nederlands', lang: 'nl' },
			},
			// Fails the build on a broken internal link or anchor, in both languages. The hook anchors on the
			// hooks page come from HookList.astro, which the validator can't see, so those are skipped.
			plugins: [
				starlightLinksValidator({
					exclude: ({ link }) => /^(\/nl)?\/reference\/hooks\/#/.test(link),
				}),
			],
			// The look of docs.wereldkundig.nl: dark only, its logo, colours and font.
			logo: { src: './src/assets/logo-wereldkundig.png', alt: 'Wereldkundig', replacesTitle: true },
			favicon: '/favicon.png',
			customCss: [
				// Self-hosted instead of Google Fonts, so visitors' IP addresses aren't sent to Google.
				'@fontsource/ubuntu/300.css',
				'@fontsource/ubuntu/400.css',
				'@fontsource/ubuntu/500.css',
				'@fontsource/ubuntu/700.css',
				'./src/styles/wereldkundig.css',
			],
			components: {
				ThemeProvider: './src/components/ThemeProvider.astro',
				ThemeSelect: './src/components/ThemeSelect.astro',
				Search: './src/components/Search.astro',
				SiteTitle: './src/components/SiteTitle.astro',
				SocialIcons: './src/components/SocialIcons.astro',
				PageTitle: './src/components/PageTitle.astro',
				Footer: './src/components/Footer.astro',
			},
			expressiveCode: {
				styleOverrides: { borderRadius: '0.625rem' },
			},
			sidebar: [
				{
					label: 'Getting started',
					translations: { nl: 'Aan de slag' },
					items: [
						{ label: 'Installation', translations: { nl: 'Installatie' }, slug: 'getting-started/installation' },
						{ label: 'Connect to the platform', translations: { nl: 'Koppelen aan het platform' }, slug: 'getting-started/connect' },
						{ label: 'Your first form', translations: { nl: 'Je eerste formulier' }, slug: 'getting-started/first-form' },
					],
				},
				{
					label: 'Guides',
					translations: { nl: 'Handleidingen' },
					items: [
						{ label: 'The subscribe field', translations: { nl: 'Het inschrijfveld' }, slug: 'guides/subscribe-field' },
						{ label: 'Feeds and field mapping', translations: { nl: 'Feeds en veldkoppeling' }, slug: 'guides/feeds-and-mapping' },
						{ label: 'Sending in the background', translations: { nl: 'Versturen op de achtergrond' }, slug: 'guides/background-sending' },
						{ label: 'Themes and the hourly refresh', translations: { nl: "Thema's en de verversing elk uur" }, slug: 'guides/themes-and-refresh' },
					],
				},
				{
					label: 'Reference',
					translations: { nl: 'Naslag' },
					items: [
						{ label: 'Hooks', translations: { nl: 'Hooks' }, slug: 'reference/hooks' },
						{ label: 'Entry notes and errors', translations: { nl: 'Notities en foutmeldingen' }, slug: 'reference/entry-notes-and-errors' },
						{ label: 'The subscribe request', translations: { nl: 'Het inschrijfverzoek' }, slug: 'reference/subscribe-request' },
						{ label: 'Capabilities', translations: { nl: 'Rechten' }, slug: 'reference/capabilities' },
						{ label: 'Stored data', translations: { nl: 'Opgeslagen gegevens' }, slug: 'reference/stored-data' },
						{ label: 'Security and privacy', translations: { nl: 'Beveiliging en privacy' }, slug: 'reference/security-and-privacy' },
					],
				},
				{ label: 'Troubleshooting', translations: { nl: 'Problemen oplossen' }, slug: 'troubleshooting' },
			],
		}),
	],
});
