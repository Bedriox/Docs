# Versioned data and gameplay catalogs

Bedriox uses the public [Data](https://github.com/Bedriox/Data) repository as
the runtime authority for reviewed, versioned Bedrock registries. The server
and Protocol consume its validated PHP API; neither component downloads data
or reads an upstream project's working files at runtime.

The current dataset includes the complete admitted creative and recipe records
for the pinned Bedrock release. Bedriox projects those records into its
authoritative inventory and crafting models while keeping item-specific
gameplay mechanics and processing stations as separately implemented
capabilities.

## Release data lifecycle

Release maintainers prepare a candidate from approved, pinned source material.
The public contract begins at the reviewed candidate and does not depend on a
particular preparation tool or workspace layout.

The release flow is:

1. Prepare a candidate for one explicit Bedrock version and protocol from a
   pinned, locally supplied source snapshot.
2. Verify schemas, bounds, references, ordering, hashes, and deterministic
   reproduction.
3. Review the semantic diff and approve the exact candidate digest.
4. Publish that approved candidate to a review branch in Data.
5. Run Data's independent admission checks before merging and pinning the new
   component release in its consumers.

Publication must stop if the candidate changed after approval, the destination
checkout is dirty, a required source is unresolved, or validation fails. An
unreviewed source checkout never becomes a runtime dependency.

## Dataset contract

An admitted dataset is immutable and identified by an explicit Bedrock version,
protocol, schema version, source revision, artifact hashes, and one overall
content digest. Canonical ordering and serialization make repeated preparation
from the same approved input byte-for-byte reproducible.

Data independently validates each admitted bundle. It rejects unknown schema
versions, duplicate identifiers or network IDs, invalid block-state references,
malformed or oversized NBT, broken creative-group references, and any artifact
whose recorded hash does not match its contents. Recipe admission also rejects
duplicate identities, malformed shapes, unresolved ingredients, tags or
outputs, unknown recipe kinds or stations, and incomplete source-to-artifact
coverage.

## Creative catalog scope

The current integration exposes the complete creative groups and entries for
the pinned Bedrock release, including distinct damage, block-state, and bounded
NBT variants of the same item identifier. Creative network IDs remain protocol
data; Bedriox translates selected entries into server-owned item stacks and
uses its existing authoritative inventory transaction flow.

The integration preserves:

- every admitted creative entry and group to encode in current-protocol order;
- group icons, categories, item counts, damage, block-state variants, and NBT
  to survive the Data-to-Protocol-to-Bedriox projection;
- creative selections to enter the inventory without disconnects or
  identifier-only variant loss;
- survival inventory, block placement, persistence, plugin-defined items, and
  command item lookup to keep their existing authoritative behavior; and
- malformed, oversized, duplicate, or unresolved data to fail before server
  startup or packet emission.

Catalog presence is not a promise that every listed item has its unique vanilla
mechanic. Food effects, bows, buckets, armor behavior, enchantments, vehicles,
spawn eggs, specialized tools, and other item-specific actions remain separate
gameplay milestones unless their owning implementation explicitly documents
support. Unsupported use must remain bounded and must not grant the client
authority over world or inventory state.

## Recipe catalog scope

The recipe registry exposes immutable shaped, shapeless, input-derived, and
station-classified records through canonical item identities. It preserves
dimensions, holes, mirroring, ingredient alternatives and tags, counts,
outputs, priorities, recipe identities, and the declared station without
making wire network IDs part of the Data API.

Bedriox admits crafting and processing records into server-owned indexed views
and projects their current wire representation through Protocol. Recipe
matching, fuel selection, scheduling, experience, and inventory mutation remain
Bedriox behavior; Data does not inspect live stations or execute a transition.
Catalog presence still does not enable an unrelated mechanic by itself. See
[crafting](crafting.md),
[processing stations and experience](processing-and-experience.md), and
[effects, particles, potions, and brewing](effects-and-particles.md).

## Updating a pinned release

Adding a newer Bedrock dataset is a coordinated release change, not a file
replacement. Maintainers must review the generated semantic diff, admit and
release Data, update Protocol only when the wire contract changes, update the
Bedriox component pins, run affected repository and workspace gates, and record
the required retail-client journey. A successful dataset build alone does not
establish client compatibility.
