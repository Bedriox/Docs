# Client journey

The cumulative client journey protects the smallest coherent server experience
from discovery through cleanup. Each implemented step has automated component
and integration evidence; wire-visible changes also require retail evidence.

1. Discover the exact bounded server advertisement.
2. Complete RakNet negotiation and connected transport setup.
3. Authenticate, negotiate encryption, and complete resource-pack setup.
4. Receive registries, player state, and the complete flat spawn view.
5. Observe correct grass, dirt, bedrock, air, biome, gravity, and breathing.
6. Move, rotate, jump, sprint, crouch, chat, and cross chunk boundaries.
7. Open and close the inventory repeatedly; move, split, select, and place from
   the server-owned starter grass stack without losing items.
8. Break and place grass with authoritative world mutation, inventory
   consumption, prediction correction, and visible progress.
9. Use ordinary controls without a valid packet being mistaken for corruption.
10. Disconnect, release all session and chunk ownership, and reconnect cleanly.
11. With two clients, verify peer visibility, movement and posture, attributed
    chat, synchronized interaction and held items, actor removal before list
    cleanup, and a ghost-free rejoin.

Malformed, spoofed, phase-invalid, or oversized traffic remains fail-closed for
the affected session. Valid but unfinished gameplay receives an explicitly
documented no-op, rejection and resynchronization, or supported response; it is
not silently treated as malformed.

Passing only an encoder/decoder round trip is insufficient. Tests use literal
vectors or independent construction where practical, verify resource bounds,
and retain the regression that motivated each fix.

The multiplayer implementation has automated coverage, but step 11 remains an
open retail qualification item. Follow the repeatable checklist in
[player and multiplayer lifecycle](player-multiplayer.md) before advancing any
support claim.
