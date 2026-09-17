(function (blocks, element, blockEditor, serverSideRender, i18n) {
  var ServerSideRender = serverSideRender.default || serverSideRender;

  blocks.registerBlockType("rakanzakat/form", {
    apiVersion: 3,
    title: __("Borang Zakat", "rakanzakat"),
    description: __("Borang bayar zakat melalui Billplz.", "rakanzakat"),
    icon: "heart",
    category: "widgets",
    supports: { html: false, align: ["wide", "full"] },
    edit: function () {
      return el(
        "div",
        blockEditor.useBlockProps(),
        el(ServerSideRender, { block: "rakanzakat/form" })
      );
    },
    save: function () {
      return null;
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.serverSideRender,
  window.wp.i18n
);
