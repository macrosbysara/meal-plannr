import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import metadata from './block.json';
import Edit from './edit';

registerBlockType( metadata.name, {
	edit: Edit,
	save: ( { attributes } ) => {
		const {
			name,
			quantityVolume,
			unitVolume,
			quantityWeight,
			unitWeight,
			notes,
		} = attributes;
		const blockProps = useBlockProps.save();
		return (
			<div { ...blockProps }>
				<span className="ingredient-name">{ name }</span>
				<span>
					{ quantityVolume } { unitVolume }
				</span>
				<span>
					{ quantityWeight } { unitWeight }
				</span>
				<span className="ingredient-notes">{ notes }</span>
			</div>
		);
	},
} );
