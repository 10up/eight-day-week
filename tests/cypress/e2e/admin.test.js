describe("Admin can login and open dashboard", () => {
  before(() => {
    cy.login();
  });

  it("Open dashboard", () => {
    cy.visit(`/wp-admin`);
    cy.get("h1").should("contain", "Dashboard");
  });
});

describe('Admin can login and make sure plugin is activated', () => {
	before(() => {
		cy.login();
	});

	it('Can activate plugin if it is deactivated', () => {
		cy.visit('/wp-admin/plugins.php');
		// Depending on how the plugin is installed the slug may be either eight-day-week or eight-day-week-print-workflow.
		cy.get(`#the-list tr[data-slug^="eight-day-week"]`).then($pluginRow => {
				if ($pluginRow.find('.activate > a').length > 0) {
						cy.get(`#the-list tr[data-slug^="eight-day-week"] .activate > a`)
								.should('have.text', 'Activate')
								.click();
				}
		});
	});
});
