import { __ } from "@wordpress/i18n";

if (typeof wp !== "undefined") {
  // Helper functions to reduce duplication
  const createAstrobinCaption = (result, formattedDate = null) => {
    const baseCaption = `<a href="https://app.astrobin.com/i/${
      result.hash
    }" target="_blank" rel="nofollow">${result.title}</a> ${__(
      "by",
      "mosne-astrobin"
    )} <a href="https://app.astrobin.com/u/${
      result.user
    }" target="_blank" rel="nofollow">${result.user}</a>`;

    if (formattedDate) {
      return `${baseCaption}<br/><em>${__(
        "Image of the Day:",
        "mosne-astrobin"
      )} ${formattedDate}</em>`;
    }

    return baseCaption;
  };

  const formatDate = (dateString) => {
    if (!dateString) return null;

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
      result.date ? formatDate(result.date) : null
    ),
    previewUrl: result.url_regular,
    url: result.url_real || result.url_hd,
  });

  // Shared fetch function to reduce code duplication
  const fetchAstrobinImages = async (
    query = {},
    type,
    searchParam = "term"
  ) => {
    try {
      const defaultArgs = {
        limit: 50,
        type: type,
      };

      const finalQuery = { ...query, ...defaultArgs };
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        search: searchParam,
        page: "page",
      };

      const url = new URL(`${mosneAstroBin.apiUrl}/search`);
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
          "X-WP-Nonce": mosneAstroBin.nonce,
        },
      });

      if (!response.ok) {
        console.error(
          `${logPrefix} API error:`,
          response.status,
          response.statusText
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
    fetchOptions = {}
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
      getReportUrl: ({ sourceId }) =>
        `https://www.astrobin.com/api/v1/image/${sourceId}/`,
      isExternalResource: true,
    });
  };

  // Register all AstroBin media categories
  registerAstrobinCategory(
    "astrobin-my-pictures",
    __("AstroBin | My Pictures", "mosne-astrobin"),
    __("Search by title", "mosne-astrobin"),
    { type: "my_pictures" }
  );

  registerAstrobinCategory(
    "astrobin-public-pictures",
    __("AstroBin | Public Pictures", "mosne-astrobin"),
    __("Search by title", "mosne-astrobin"),
    { type: "public_pictures" }
  );

  registerAstrobinCategory(
    "astrobin-other-user-gallery",
    __("AstroBin | By Username", "mosne-astrobin"),
    __("Search by username", "mosne-astrobin"),
    { type: "user_gallery", searchParam: "username" }
  );

  registerAstrobinCategory(
    "astrobin-by-subject",
    __("AstroBin | By Subject", "mosne-astrobin"),
    __("Search by subject (e.g. M31)", "mosne-astrobin"),
    { type: "by_subject" }
  );

  registerAstrobinCategory(
    "astrobin-image-of-the-day",
    __("AstroBin | Image of the Day", "mosne-astrobin"),
    __("Browse past images", "mosne-astrobin"),
    { type: "imageoftheday" }
  );
}
