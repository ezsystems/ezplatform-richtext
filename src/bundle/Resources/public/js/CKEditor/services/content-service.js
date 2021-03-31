export const findContent = ({ token, siteaccess, contentId, limit = 1, offset = 0 }, callback) => {
    const body = JSON.stringify({
        ViewInput: {
            identifier: `find-content-${contentId}`,
            public: false,
            ContentQuery: {
                FacetBuilders: {},
                SortClauses: {},
                Filter: { ContentIdCriterion: `${contentId}` },
                limit,
                offset,
            },
        },
    });
    const request = new Request('/api/ezp/v2/views', {
        method: 'POST',
        headers: {
            Accept: 'application/vnd.ez.api.View+json; version=1.1',
            'Content-Type': 'application/vnd.ez.api.ViewInput+json; version=1.1',
            'X-Siteaccess': siteaccess,
            'X-CSRF-Token': token,
        },
        body,
        mode: 'same-origin',
        credentials: 'same-origin',
    });

    fetch(request)
        .then(window.eZ.helpers.request.getJsonFromResponse)
        .then((response) => {
            const items = response.View.Result.searchHits.searchHit.map((searchHit) => searchHit.value.Content);

            callback(items);
        })
        .catch(window.eZ.helpers.notification.showErrorNotification);
};
