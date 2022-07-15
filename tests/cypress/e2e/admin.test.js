describe("Admin can login and open dashboard", () => {
  before(() => {
    cy.login();
  });

  it("Open dashboard", () => {
    cy.visit(`/wp-admin`);
    cy.get("h1").should("contain", "Dashboard");
  });

  it("Activate Hello Dolly and deactivate it back", () => {
    cy.activatePlugin("hello-dolly");
    cy.deactivatePlugin("hello-dolly");
  });
});

describe('Admin can login and make sure plugin is activated', () => {
	before(() => {
		cy.login();
	});

	it('Can activate plugin if it is deactivated', () => {
		cy.activatePlugin('eight-day-week-print-workflow');
	});
});