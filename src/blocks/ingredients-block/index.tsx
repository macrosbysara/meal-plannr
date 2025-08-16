import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
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
		const hasWeight = 0 !== quantityWeight;
		const hasVolume = 0 !== quantityVolume;
		return (
			<li { ...blockProps }>
				<span className="ingredient-name">{ name }</span>
				<span className="ingredient-notes">{ notes }</span>
				{ hasVolume && (
					<span>
						{ quantityVolume } { unitVolume }
					</span>
				) }
				{ hasWeight &&
					( hasVolume ? (
						<span>
							({ quantityWeight } { unitWeight })
						</span>
					) : (
						<span>
							{ quantityWeight } { unitWeight }
						</span>
					) ) }
			</li>
		);
	},
} );
