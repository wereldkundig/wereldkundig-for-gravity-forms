import { defineCollection } from 'astro:content';
import { z } from 'astro/zod';
import { docsLoader, i18nLoader } from '@astrojs/starlight/loaders';
import { docsSchema, i18nSchema } from '@astrojs/starlight/schema';

export const collections = {
	docs: defineCollection({ loader: docsLoader(), schema: docsSchema() }),
	// Starlight's own interface strings come with Starlight; these are the strings of our component overrides,
	// per language in src/content/i18n/{en,nl}.json, read with Astro.locals.t().
	i18n: defineCollection({
		loader: i18nLoader(),
		schema: i18nSchema({
			extend: z.object({
				'wk.product': z.string(),
				'wk.footer.tagline': z.string(),
				'wk.mainDocs': z.string(),
				'wk.hooks.filter': z.string(),
				'wk.hooks.use': z.string(),
				'wk.hooks.parameter': z.string(),
				'wk.hooks.type': z.string(),
				'wk.hooks.description': z.string(),
			}),
		}),
	}),
};
