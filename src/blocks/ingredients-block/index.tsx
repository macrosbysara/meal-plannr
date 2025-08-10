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
		const blockProps = useBlockProps.save( {
			style: {
				display: 'flex',
				gap: '1rem',
				flexWrap: 'wrap',
				alignItems: 'center',
			},
		} );
		return (
			<li { ...blockProps }>
				<span className="ingredient-name">{ name }</span>
				<span className="ingredient-notes">{ notes }</span>
				{ quantityVolume && (
					<span>
						{ quantityVolume } { unitVolume }
					</span>
				) }
				{ quantityWeight && (
					<span>
						{ quantityWeight } { unitWeight }
					</span>
				) }
			</li>
		);
	},
} );
