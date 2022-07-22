describe('Publish a new print issue', () => {
	before(() => {
		cy.login();
	});

	it("Open new print issue page", () => {
		cy.visit(`/wp-admin/post-new.php?post_type=print-issue`);
		cy.get("#title").type("Print Title 1");
		cy.get("#pi-section-add").click();
		cy.get("#pi-section-name").type("Section title 1");
		cy.get("#pi-section-add-confirm").click();
		cy.get(".pi-article-add:visible").contains('Add Article').should('exist').click();
		cy.get(".pi-article-title:visible").type("Hello");
		cy.get("body").then($body => {
			if ($body.find(".ui-menu-item").length > 0) {
				cy.get(".ui-menu-item").should('exist').click();
			} else {
				cy.get(".ui-menu-item-wrapper").should('exist').click();
			}
		});
		cy.get("#normal-sortables .button-primary:visible").click();
	});
});