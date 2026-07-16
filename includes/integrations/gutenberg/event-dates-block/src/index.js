import { useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

function Edit() {
  const blockProps = useBlockProps();

  return (
    <div { ...blockProps }>
      <Placeholder
        icon="calendar-alt"
        label={ __( 'WP Seed — Dates de l’événement', 'wp-seed-events' ) }
        instructions={ __(
          'Le rendu serveur et les réglages seront ajoutés dans les lots suivants.',
          'wp-seed-events',
        ) }
      />
    </div>
  );
}

registerBlockType( metadata.name, {
  ...metadata,
  edit: Edit,
  save: () => null,
} );
