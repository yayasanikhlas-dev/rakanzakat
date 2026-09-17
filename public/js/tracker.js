(function () {
  if (typeof RakanZakatTrack === "undefined") return;
  if (/\/wp-admin(\/|$)/.test(location.pathname)) return;

  function uid() {
    return "rz_" + Math.random().toString(36).slice(2) + Date.now().toString(36);
  }

  var visitorId;
  var sessionId;
  var isNewSession = false;
  try {
    visitorId = localStorage.getItem("rz_vid");
    if (!visitorId) {
      visitorId = uid();
      localStorage.setItem("rz_vid", visitorId);
    }
    sessionId = sessionStorage.getItem("rz_sid");
    if (!sessionId) {
      sessionId = uid();
      sessionStorage.setItem("rz_sid", sessionId);
      isNewSession = true;
    }
    if (!sessionStorage.getItem("rz_land")) {
      sessionStorage.setItem("rz_land", location.href);
    }
    var params = new URLSearchParams(location.search);
    ["utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term"].forEach(function (key) {
      var val = params.get(key);
      if (val) sessionStorage.setItem("rz_" + key, val);
    });
  } catch (e) {
    visitorId = uid();
    sessionId = uid();
    isNewSession = true;
  }

  var payload = {
    visitor_id: visitorId,
    session_id: sessionId,
    is_new_session: isNewSession ? 1 : 0,
    page_url: location.href,
    page_title: document.title,
    referrer: document.referrer || "",
    landing_page: (function () {
      try {
        return sessionStorage.getItem("rz_land") || location.href;
      } catch (e) {
        return location.href;
      }
    })(),
    utm_source: new URLSearchParams(location.search).get("utm_source") || (sessionStorage.getItem("rz_utm_source") || ""),
    utm_medium: new URLSearchParams(location.search).get("utm_medium") || (sessionStorage.getItem("rz_utm_medium") || ""),
    utm_campaign: new URLSearchParams(location.search).get("utm_campaign") || (sessionStorage.getItem("rz_utm_campaign") || ""),
    utm_content: new URLSearchParams(location.search).get("utm_content") || (sessionStorage.getItem("rz_utm_content") || ""),
    utm_term: new URLSearchParams(location.search).get("utm_term") || (sessionStorage.getItem("rz_utm_term") || ""),
  };

  fetch(RakanZakatTrack.endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
    keepalive: true,
  }).catch(function () {});
})();
