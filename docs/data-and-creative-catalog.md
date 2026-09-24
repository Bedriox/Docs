# Versioned data and the creative catalog

Bedriox uses the public [Data](https://github.com/Bedriox/Data) repository as
the runtime authority for reviewed, versioned Bedrock registries. The server
and Protocol consume its validated PHP API; neither component downloads data
or reads an upstream project's working files at runtime.

The current dataset includes the complete admitted creative catalog for the
pinned Bedrock release. Bedriox projects that catalog into its authoritative
inventory model while keeping item-specific gameplay mechanics as separately
implemented capabilities.

## Release data lifecycle

Release maintainers prepare candidate data with the private
[DataBuilder](https://github.com/Bedriox/DataBuilder) repository. Access to that
repository is restricted. Its maintained instructions are the authority for
the exact commands and review workflow; public documentation intentionally does
not reproduce private implementation details or machine-specific paths.

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
checkout is dirty, a required reference is unresolved, or validation fails.
DataBuilder never publishes directly to a protected branch and never makes an
unreviewed upstream checkout a runtime dependency.

## Dataset contract

An admitted dataset is immutable and identified by an explicit Bedrock version,
protocol, schema version, source revision, artifact hashes, and one overall
content digest. Canonical ordering and serialization make repeated preparation
from the same approved input byte-for-byte reproducible.

Data independently validates each admitted bundle. It rejects unknown schema
versions, duplicate identifiers or network IDs, invalid block-state references,
malformed or oversized NBT, broken creative-group references, and any artifact
whose recorded hash does not match its contents.

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

## Updating a pinned release

Adding a newer Bedrock dataset is a coordinated release change, not a file
replacement. Maintainers must review the generated semantic diff, admit and
release Data, update Protocol only when the wire contract changes, update the
Bedriox component pins, run affected repository and workspace gates, and record
the required retail-client journey. A successful dataset build alone does not
establish client compatibility.
