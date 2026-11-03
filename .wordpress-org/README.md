# WordPress.org listing assets

Everything in this folder is copied to the plugin's SVN `assets/` folder on WordPress.org, including this file:

- by `.github/workflows/release.yml`, as part of every stable release;
- by `.github/workflows/plugin-assets.yml`, on a push to `main` that changes only this folder and/or `readme.txt`, or when run by hand. Use it for new screenshots, a new banner or a readme fix between releases.

It is `export-ignore` in `.gitattributes`, so it is never part of the plugin itself.

## Files

WordPress.org picks these up by name. Names are lowercase; screenshots are numbered from 1 without gaps.

| File | Size | Where it shows |
|---|---|---|
| `icon-128x128.png` | 128×128 | Plugin icon |
| `icon-256x256.png` | 256×256 | Plugin icon, retina |
| `banner-772x250.png` | 772×250 | Header of the plugin page |
| `banner-1544x500.png` | 1544×500 | Header of the plugin page, retina |
| `screenshot-1.png`, `screenshot-2.png`, … | any | Screenshots section |

PNG or JPG; an icon may also be `icon.svg`. Each file is at most 10 MB.

Captions are in `readme.txt` under `== Screenshots ==`, one numbered line per screenshot, in the same order. Adding a screenshot means adding its caption in the same place. Take screenshots with the admin in English, without debug bars or admin notices.

See https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/ for the full rules.
