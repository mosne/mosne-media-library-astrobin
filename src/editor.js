// media library
import { registerInserterMediaCategory } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
if (typeof wp !== "undefined") {
  registerInserterMediaCategory({
    name: "astrobin-my-pictures",
    labels: {
      name: __("AstroBin | My pictures", "mosne-astrobin"),
      search_items: __("Search by title", "mosne-astrobin"),
    },
    mediaType: "image",
    async fetch(query = {}) {
      const defaultArgs = {
        limit: query.per_page,
      };
      console.log(query);
      const finalQuery = { ...query, ...defaultArgs };
      // Sometimes you might need to map the supported request params according to `InserterMediaRequest`.
      // interface. In this example the `search` query param is named `q`.
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        search: "title__icontains",
      };
      const url = new URL(
        `${mosneAstroBin.apiUrl}/search?term=${encodeURIComponent(query.search || '')}`
      );
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
      const results = jsonResponse.objects;
      return results.map((result) => ({
        // ...result,
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

  // public pictures
  registerInserterMediaCategory({
    name: "astrobin-public-pictures",
    labels: {
      name: __("AstroBin | Public pictures", "mosne-astrobin"),
      search_items: __("Search by title", "mosne-astrobin"),
    },
    mediaType: "image",
    async fetch(query = {}) {
      const defaultArgs = {
        limit: query.per_page,
      };
      console.log(query);
      const finalQuery = { ...query, ...defaultArgs };
      // Sometimes you might need to map the supported request params according to `InserterMediaRequest`.
      // interface. In this example the `search` query param is named `q`.
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        search: "title__icontains",
      };
      const url = new URL(
        `${mosneAstroBin.apiUrl}/search?term=${encodeURIComponent(query.search || '')}`
      );
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
      const results = jsonResponse.objects;
      return results.map((result) => ({
        // ...result,
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

  // public in user's gallery
  registerInserterMediaCategory({
    name: "astrobin-other-user-gallery",
    labels: {
      name: __("AstroBin | Users' galleries", "mosne-astrobin"),
      search_items: __("Search by username", "mosne-astrobin"),
    },
    mediaType: "image",
    async fetch(query = {}) {
      const defaultArgs = {
        limit: query.per_page,
      };
      console.log(query);
      const finalQuery = { ...query, ...defaultArgs };
      // Sometimes you might need to map the supported request params according to `InserterMediaRequest`.
      // interface. In this example the `search` query param is named `q`.
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        search: "title__icontains",
      };
      const url = new URL(
        `${mosneAstroBin.apiUrl}/search?term=${encodeURIComponent(query.search || '')}`
      );
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
      const results = jsonResponse.objects;
      return results.map((result) => ({
        // ...result,
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

  // public in user's gallery
  registerInserterMediaCategory({
    name: "astrobin-image-of-the-day",
    labels: {
      name: __("AstroBin | Image of the day", "mosne-astrobin"),
      search_items: __("Search by title", "mosne-astrobin"),
    },
    mediaType: "image",
    async fetch(query = {}) {
      const defaultArgs = {
        limit: query.per_page,
      };
      console.log(query);
      const finalQuery = { ...query, ...defaultArgs };
      // Sometimes you might need to map the supported request params according to `InserterMediaRequest`.
      // interface. In this example the `search` query param is named `q`.
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        search: "title__icontains",
      };
      const url = new URL(
        `${mosneAstroBin.apiUrl}/search?term=${encodeURIComponent(query.search || '')}`
      );
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
      const results = jsonResponse.objects;
      return results.map((result) => ({
        // ...result,
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
}
