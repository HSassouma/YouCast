# YouCast — Design direction

## Product idea

YouCast is the BDE activity platform for YouCode learners and organizers. The
interface should make it easy to discover activities, join with peers, and
share ideas that can become real events.

## Current design direction

YouCast uses a fresh blue-and-mauve identity, inspired by the cool, polished
learning-platform palette on Youcode Tracker:

- **Palette:** cool lavender canvas `#F5F6FF`, deep indigo ink `#20213A`,
  confident blue `#4D63DB`, and mauve `#7563D5` with a pale lavender accent.
- **Typography:** Space Grotesk for display headings and Figtree for interface
  text, with system fallbacks.
- **Surfaces:** cool-tinted borders, clear white cards, soft blue-violet
  elevation, and open spacing.
- **Signature detail:** a three-part campus cycle—idea, building together,
  sharing what was learned—shown as a typographic workshop board rather than a
  fabricated product mock or stock image.
- **Motion:** a slow ambient background wash, a gentle animated landing hero,
  and responsive card/button hover feedback. Reduced-motion preferences are
  respected.

## Product grounding

The direction reflects YouCode's public description of its pedagogy: practical
project-based learning, individual and group work, iterative practice, and
learners teaching one another. YouCast copy and navigation center proposing,
participating, collaborating, and sharing.

## Areas updated

- Landing page and about page
- Public and authenticated navigation and footer
- Activity catalogue cards and detail panels through shared design tokens
- Learner dashboard and administration views through the shared page shell,
  panels, tables, form controls, and buttons
- Authentication layout and shared button components

The work changes frontend presentation only. Routes, controllers, policies,
database schema, validation rules, and application behavior are untouched.

## Verification

- The shared design tokens carry the palette through public, learner, and admin
  pages without changing page structure or application behavior.
