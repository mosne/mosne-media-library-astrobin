import { __ } from "@wordpress/i18n";
import { registerInserterMediaCategory } from "@wordpress/block-editor";

if (typeof wp !== "undefined") {
  // My AstroBin Pictures
  wp.data.dispatch('core/block-editor').registerInserterMediaCategory({
    name: "astrobin-my-pictures",
    labels: {
      name: __("AstroBin | My pictures", "mosne-astrobin"),
      search_items: __("Search by title", "mosne-astrobin"),
    },
    mediaType: "image",
    async fetch(query = {}) {
      const defaultArgs = {
        limit: query.per_page,
        type: "my_pictures",
      };
      console.log(query);
      const finalQuery = { ...query, ...defaultArgs };
      // Sometimes you might need to map the supported request params according to `InserterMediaRequest`.
      // interface. In this example the `search` query param is named `q`.
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        search: "term",
        page: "page",
      };
      const url = new URL(`${mosneAstroBin.apiUrl}/search`);
      Object.entries(finalQuery).forEach(([key, value]) => {
        const queryKey = mapFromInserterMediaRequest[key] || key;
        url.searchParams.set(queryKey, value);
      });
      const response = await window.fetch(url, {
        headers: {
          "X-WP-Nonce": mosneAstroBin.nonce,
        },
      });
      const jsonResponse = await response.json();
      const results = jsonResponse.objects || [];
      return results.map((result) => ({
        // If your response result includes an `id` prop that you want to access later, it should
        // be mapped to `InserterMediaItem`'s `sourceId` prop. This can be useful if you provide
        // a report URL getter.
        // Additionally you should always clear the `id` value of your response results because
        // it is used to identify WordPress media items.
        sourceId: result.id,
        id: undefined,
        title: result.title,
        hash: result.hash,
        caption: `<a href="https://app.astrobin.com/i/${result.hash}" target="_blank" rel="nofollow">${result.title}</a> by <a href="https://app.astrobin.com/u/${result.user}" target="_blank" rel="nofollow">${result.user}</a>`,
        previewUrl: result.url_duckduckgo,
        url: result.url_real,
      }));
    },
    getReportUrl: ({ sourceId }) =>
      `https://www.astrobin.com/api/v1/image/${sourceId}/`,
    isExternalResource: true,
  });

  // Public pictures
  wp.data.dispatch('core/block-editor').registerInserterMediaCategory({
    name: "astrobin-public-pictures",
    labels: {
      name: __("AstroBin | Public pictures", "mosne-astrobin"),
      search_items: __("Search by title", "mosne-astrobin"),
    },
    mediaType: "image",
    async fetch(query = {}) {
      const defaultArgs = {
        limit: query.per_page,
        type: "public_pictures",
      };
      console.log(query);
      const finalQuery = { ...query, ...defaultArgs };
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        search: "term",
        page: "page",
      };
      const url = new URL(`${mosneAstroBin.apiUrl}/search`);
      Object.entries(finalQuery).forEach(([key, value]) => {
        const queryKey = mapFromInserterMediaRequest[key] || key;
        url.searchParams.set(queryKey, value);
      });
      const response = await window.fetch(url, {
        headers: {
          "X-WP-Nonce": mosneAstroBin.nonce,
        },
      });
      const jsonResponse = await response.json();
      const results = jsonResponse.objects || [];
      return results.map((result) => ({
        sourceId: result.id,
        id: undefined,
        title: result.title,
        hash: result.hash,
        caption: `<a href="https://app.astrobin.com/i/${result.hash}" target="_blank" rel="nofollow">${result.title}</a> by <a href="https://app.astrobin.com/u/${result.user}" target="_blank" rel="nofollow">${result.user}</a>`,
        previewUrl: result.url_duckduckgo,
        url: result.url_real,
      }));
    },
    getReportUrl: ({ sourceId }) =>
      `https://www.astrobin.com/api/v1/image/${sourceId}/`,
    isExternalResource: true,
  });

  // Users' galleries
  wp.data.dispatch('core/block-editor').registerInserterMediaCategory({
    name: "astrobin-other-user-gallery",
    labels: {
      name: __("AstroBin | Users' galleries", "mosne-astrobin"),
      search_items: __("Search by username", "mosne-astrobin"),
    },
    mediaType: "image",
    async fetch(query = {}) {
      const defaultArgs = {
        limit: query.per_page,
        type: "user_gallery",
      };
      console.log(query);
      const finalQuery = { ...query, ...defaultArgs };
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        search: "username", // In this case, search is for usernames
        page: "page",
      };
      const url = new URL(`${mosneAstroBin.apiUrl}/search`);
      Object.entries(finalQuery).forEach(([key, value]) => {
        const queryKey = mapFromInserterMediaRequest[key] || key;
        url.searchParams.set(queryKey, value);
      });
      const response = await window.fetch(url, {
        headers: {
          "X-WP-Nonce": mosneAstroBin.nonce,
        },
      });
      const jsonResponse = await response.json();
      const results = jsonResponse.objects || [];
      return results.map((result) => ({
        sourceId: result.id,
        id: undefined,
        title: result.title,
        hash: result.hash,
        caption: `<a href="https://app.astrobin.com/i/${result.hash}" target="_blank" rel="nofollow">${result.title}</a> by <a href="https://app.astrobin.com/u/${result.user}" target="_blank" rel="nofollow">${result.user}</a>`,
        previewUrl: result.url_duckduckgo,
        url: result.url_real,
      }));
    },
    getReportUrl: ({ sourceId }) =>
      `https://www.astrobin.com/api/v1/image/${sourceId}/`,
    isExternalResource: true,
  });

  // Image of the day
  wp.data.dispatch('core/block-editor').registerInserterMediaCategory({
    name: "astrobin-image-of-the-day",
    labels: {
      name: __("AstroBin | Image of the day", "mosne-astrobin"),
      search_items: __("Browse past images", "mosne-astrobin"),
    },
    mediaType: "image",
    async fetch(query = {}) {
      const defaultArgs = {
        limit: query.per_page,
        type: "image_of_the_day",
      };
      console.log(query);
      const finalQuery = { ...query, ...defaultArgs };
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        page: "page", // Page in this context will get different IOTDs
      };
      const url = new URL(`${mosneAstroBin.apiUrl}/search`);
      Object.entries(finalQuery).forEach(([key, value]) => {
        const queryKey = mapFromInserterMediaRequest[key] || key;
        url.searchParams.set(queryKey, value);
      });
      const response = await window.fetch(url, {
        headers: {
          "X-WP-Nonce": mosneAstroBin.nonce,
        },
      });
      const jsonResponse = await response.json();
      const results = jsonResponse.objects || [];
      return results.map((result) => ({
        sourceId: result.id,
        id: undefined,
        title: result.title,
        hash: result.hash,
        // For Image of the Day, include the date in the caption if available
        caption: result.date 
          ? `<a href="https://app.astrobin.com/i/${result.hash}" target="_blank" rel="nofollow">${result.title}</a> by <a href="https://app.astrobin.com/u/${result.user}" target="_blank" rel="nofollow">${result.user}</a><br/><em>Image of the Day: ${new Date(result.date).toLocaleDateString()}</em>`
          : `<a href="https://app.astrobin.com/i/${result.hash}" target="_blank" rel="nofollow">${result.title}</a> by <a href="https://app.astrobin.com/u/${result.user}" target="_blank" rel="nofollow">${result.user}</a>`,
        previewUrl: result.url_duckduckgo,
        url: result.url_real,
      }));
    },
    getReportUrl: ({ sourceId }) =>
      `https://www.astrobin.com/api/v1/image/${sourceId}/`,
    isExternalResource: true,
  });
  
  // Top Picks
  wp.data.dispatch('core/block-editor').registerInserterMediaCategory({
    name: "astrobin-top-picks",
    labels: {
      name: __("AstroBin | Top Picks", "mosne-astrobin"),
      search_items: __("Explore top picks", "mosne-astrobin"),
    },
    mediaType: "image",
    async fetch(query = {}) {
      const defaultArgs = {
        limit: query.per_page,
        type: "top_picks",
      };
      console.log(query);
      const finalQuery = { ...query, ...defaultArgs };
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        page: "page",
      };
      const url = new URL(`${mosneAstroBin.apiUrl}/search`);
      Object.entries(finalQuery).forEach(([key, value]) => {
        const queryKey = mapFromInserterMediaRequest[key] || key;
        url.searchParams.set(queryKey, value);
      });
      const response = await window.fetch(url, {
        headers: {
          "X-WP-Nonce": mosneAstroBin.nonce,
        },
      });
      const jsonResponse = await response.json();
      const results = jsonResponse.objects || [];
      return results.map((result) => ({
        sourceId: result.id,
        id: undefined,
        title: result.title,
        hash: result.hash,
        caption: `<a href="https://app.astrobin.com/i/${result.hash}" target="_blank" rel="nofollow">${result.title}</a> by <a href="https://app.astrobin.com/u/${result.user}" target="_blank" rel="nofollow">${result.user}</a>`,
        previewUrl: result.url_duckduckgo,
        url: result.url_real,
      }));
    },
    getReportUrl: ({ sourceId }) =>
      `https://www.astrobin.com/api/v1/image/${sourceId}/`,
    isExternalResource: true,
  });
}
