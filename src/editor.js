import { __ } from "@wordpress/i18n";

if (typeof wp !== "undefined") {
	// Helper functions to reduce duplication
	const createAstrobinCaption = (result, formattedDate = null) => {
		const baseCaption = `<a href="https://app.astrobin.com/i/${
			result.hash
		}" target="_blank" rel="nofollow noreferrer">${result.title}</a> ${__(
			"by",
			"mosne-media-library-astronomy",
		)} <a href="https://app.astrobin.com/u/${
			result.username
		}" target="_blank" rel="nofollow noreferrer">${
			result.username
		}</a> &copy; <a href="${
			result.license_url
		}" target="_blank" rel="nofollow noreferrer">${result.license_name}</a>
    `;

		if (formattedDate) {
			return `${baseCaption}<br/><em>${__(
				"Image of the Day:",
				"mosne-media-library-astronomy",
			)} ${formattedDate}</em>`;
		}

		return baseCaption;
	};

	const formatDate = (dateString) => {
		if (!dateString) {
			return null;
		}

		try {
			const dateObj = new Date(dateString);
			return dateObj.toLocaleDateString(undefined, {
				year: "numeric",
				month: "long",
				day: "numeric",
			});
		} catch (e) {
			return dateString;
		}
	};

	const mapToMediaItem = (result) => ({
		sourceId: result.id,
		id: undefined,
		title: result.title || "",
		hash: result.hash || "",
		caption: createAstrobinCaption(
			result,
			result.date ? formatDate(result.date) : null,
		),
		previewUrl: result.thumbnail_url,
		url: result.url_real || result.url_hd,
	});

	// Shared fetch function to reduce code duplication
	const fetchAstrobinImages = async (
		query = {},
		type,
		searchParam = "term",
	) => {
		try {
			const defaultArgs = {
				limit: 30,
				type,
			};

			const finalQuery = { ...query, ...defaultArgs };
			const mapFromInserterMediaRequest = {
				per_page: "page_size",
				search: searchParam,
				page: "page",
			};

			// Determine the correct endpoint based on the type
			let endpoint = "/astrobin/search";
			if (type.startsWith("nasa_")) {
				endpoint = "/nasa/search";
			}

			const url = new URL(`${mosneAstronomy.apiUrl}${endpoint}`);
			Object.entries(finalQuery).forEach(([key, value]) => {
				const queryKey = mapFromInserterMediaRequest[key] || key;
				if (value !== undefined && value !== null) {
					url.searchParams.set(queryKey, value);
				}
			});

			const logPrefix = type === "imageoftheday" ? "IOTD" : type;

			const response = await window.fetch(url, {
				credentials: "same-origin",
				headers: {
					"X-WP-Nonce": mosneAstronomy.nonce,
				},
			});

			if (!response.ok) {
				console.error(
					`${logPrefix} API error:`,
					response.status,
					response.statusText,
				);
				const errorText = await response.text();
				console.error("Error details:", errorText);
				return [];
			}

			const jsonResponse = await response.json();
			const results = jsonResponse.objects || [];

			if (results.length === 0) {
				console.error(`No ${logPrefix} results found`);
				return [];
			}

			return results.map(mapToMediaItem);
		} catch (error) {
			console.error(`Error fetching ${type}:`, error);
			return [];
		}
	};

	// Register media categories with less duplicate code
	const registerAstrobinCategory = (
		name,
		displayName,
		searchHint,
		fetchOptions = {},
	) => {
		const { type, searchParam } = {
			type: name.replace("astrobin-", ""),
			searchParam: "term",
			...fetchOptions,
		};

		wp.data.dispatch("core/block-editor").registerInserterMediaCategory({
			name,
			labels: {
				name: displayName,
				search_items: searchHint,
			},
			mediaType: "image",
			async fetch(query = {}) {
				return fetchAstrobinImages(query, type, searchParam);
			},
		});
	};

	// Register all AstroBin media categories
	registerAstrobinCategory(
		"astrobin-my-pictures",
		__("AstroBin | My Pictures", "mosne-media-library-astronomy"),
		__("Search by title", "mosne-media-library-astronomy"),
		{ type: "astrobin_my_pictures" },
	);

	registerAstrobinCategory(
		"astrobin-public-pictures",
		__("AstroBin | Public Pictures", "mosne-media-library-astronomy"),
		__("Search by title", "mosne-media-library-astronomy"),
		{ type: "astrobin_public_pictures" },
	);

	registerAstrobinCategory(
		"astrobin-other-user-gallery",
		__("AstroBin | By Username", "mosne-media-library-astronomy"),
		__("Search by username", "mosne-media-library-astronomy"),
		{ type: "astrobin_user_gallery", searchParam: "username" },
	);

	registerAstrobinCategory(
		"astrobin-by-subject",
		__("AstroBin | By Subject", "mosne-media-library-astronomy"),
		__("Search by subject", "mosne-media-library-astronomy"),
		{ type: "astrobin_by_subject" },
	);

	registerAstrobinCategory(
		"astrobin-by-hash",
		__("AstroBin | By Image Hash", "mosne-media-library-astronomy"),
		__("Search by hash (ID)", "mosne-media-library-astronomy"),
		{ type: "astrobin_by_hash" },
	);

	registerAstrobinCategory(
		"astrobin-image-of-the-day",
		__("AstroBin | Image of the Day", "mosne-media-library-astronomy"),
		__("Browse past images", "mosne-media-library-astronomy"),
		{ type: "astrobin_imageoftheday" },
	);

	registerAstrobinCategory(
		"nasa-image-of-the-day",
		__("NASA | Image of the Day", "mosne-media-library-astronomy"),
		__("Browse past images", "mosne-media-library-astronomy"),
		{ type: "nasa_imageoftheday" },
	);

	registerAstrobinCategory(
		"nasa-planetary",
		__("NASA | Planetary", "mosne-media-library-astronomy"),
		__("Browse past images", "mosne-media-library-astronomy"),
		{ type: "nasa_planetary" },
	);

	registerAstrobinCategory(
		"nasa-mars",
		__("NASA | Mars", "mosne-media-library-astronomy"),
		__("Browse past images", "mosne-media-library-astronomy"),
		{ type: "nasa_mars" },
	);
}
